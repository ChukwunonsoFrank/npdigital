<?php

use App\Livewire\Admin\MergeProducts;
use App\Livewire\Dashboard\Start;
use App\Models\CompletedTask;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the merge products page for admins only', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard.merge-products'))
        ->assertOk()
        ->assertSee('Merge Products')
        ->assertSee('No merge products are assigned yet.');

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get(route('admin.dashboard.merge-products'))
        ->assertForbidden();
});

it('assigns products from any level as a five percent merge task at the chosen position', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 10000, 'tasks_completed' => 3,
    ]);
    $normal = WarehouseProduct::factory()->create(['level' => 'normal', 'name' => 'Cross Level Normal', 'price' => 60]);
    $gold = WarehouseProduct::factory()->create(['level' => 'gold', 'name' => 'Cross Level Gold', 'price' => 50]);

    Livewire::actingAs($admin)->test(MergeProducts::class)
        ->call('openModal', $user->id)
        ->set('productSearch', 'Cross Level')
        ->assertSee('Cross Level Normal')
        ->assertSee('Cross Level Gold')
        ->call('addProduct', $normal->product_id)
        ->call('addProduct', $gold->product_id)
        ->set('position', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertDispatched('toast');

    $task = CompletedTask::query()->where('user_id', $user->id)->sole();

    expect($task->is_merge)->toBeTrue()
        ->and($task->title)->toBe('Cross Level Normal + Cross Level Gold')
        ->and($task->cost)->toBe('110.00')
        ->and((float) $task->commission_percentage)->toBe(5.0)
        ->and($task->merge_position)->toBe(2)
        ->and($task->available_after_tasks)->toBe(4)
        ->and($task->status)->toBe('queued')
        ->and($task->merge_items)->toHaveCount(2)
        ->and(array_column($task->merge_items, 'level'))->toBe(['normal', 'gold']);

    expect($user->fresh()->negative_balance)->toBe(0);
});

it('requires the merge cost to exceed the user balance', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'balance' => 10000, 'tasks_completed' => 0]);
    $product = WarehouseProduct::factory()->create(['name' => 'Affordable Merge', 'price' => 200]);

    Livewire::actingAs($admin)->test(MergeProducts::class)
        ->call('openModal', $user->id)
        ->set('productSearch', 'Affordable Merge')
        ->call('addProduct', $product->product_id)
        ->set('selectedProducts.0.price', '90.00')
        ->call('save')
        ->assertHasErrors('selectedProducts');

    expect(CompletedTask::query()->count())->toBe(0);
});

it('rejects a merge position beyond the remaining products in the set', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'balance' => 10000, 'tasks_completed' => 29]);
    $product = WarehouseProduct::factory()->create(['name' => 'Position Merge', 'price' => 200]);

    Livewire::actingAs($admin)->test(MergeProducts::class)
        ->call('openModal', $user->id)
        ->set('productSearch', 'Position Merge')
        ->call('addProduct', $product->product_id)
        ->set('position', 2)
        ->call('save')
        ->assertHasErrors('position');

    expect(CompletedTask::query()->count())->toBe(0);
});

it('removes queued and pending merge assignments and clears the pending shortfall', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'negative_balance' => 3000]);
    $queued = $user->tasks()->create([
        'title' => 'Queued merge', 'cost' => '20.00', 'task_image_path' => '',
        'status' => 'queued', 'rating' => '0', 'rating_id' => 'queued-merge',
        'is_merge' => true, 'merge_items' => [], 'merge_position' => 1,
    ]);
    $pending = $user->tasks()->create([
        'title' => 'Pending merge', 'cost' => '80.00', 'task_image_path' => '',
        'status' => 'pending', 'rating' => '0', 'rating_id' => 'pending-merge',
        'is_merge' => true, 'merge_items' => [], 'merge_position' => 2,
    ]);

    $component = Livewire::actingAs($admin)->test(MergeProducts::class)
        ->call('removeAssignment', $queued->id)
        ->assertDispatched('toast', message: 'Merge product removed.');

    expect(CompletedTask::query()->find($queued->id))->toBeNull()
        ->and($user->fresh()->negative_balance)->toBe(3000);

    $component->call('removeAssignment', $pending->id);

    expect(CompletedTask::query()->find($pending->id))->toBeNull()
        ->and($user->fresh()->negative_balance)->toBe(0);
});

it('shows the merge shortfall as a bold negative and blocks submission until the top up clears it', function () {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 5000,
        'training_balance' => 0, 'has_made_first_deposit' => true,
        'tasks_completed' => 0, 'task_batch' => 0, 'negative_balance' => 0,
    ]);
    $merge = $user->tasks()->create([
        'title' => 'Merge Bundle', 'cost' => '80.00', 'task_image_path' => '',
        'commission_percentage' => 5, 'status' => 'queued', 'rating' => '0',
        'rating_id' => 'merge-flow', 'is_merge' => true,
        'merge_items' => [
            ['id' => 'one', 'name' => 'First Merge Item', 'price' => 40],
            ['id' => 'two', 'name' => 'Second Merge Item', 'price' => 40],
        ],
        'merge_position' => 1, 'available_after_tasks' => 0,
    ]);

    $component = Livewire::actingAs($user)->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->assertSee('First Merge Item')
        ->assertSee('Second Merge Item')
        ->assertSee('Combined cost')
        ->assertSee('$4.00')
        ->assertSee('5%')
        ->assertSee('-$30.00')
        ->assertSee('Top Up Wallet')
        ->assertDontSee('Submit Task');

    expect($user->fresh()->negative_balance)->toBe(3000)
        ->and($merge->fresh()->status)->toBe('pending');

    $component->call('submitTask')
        ->assertDispatched('insufficient-balance');

    expect($merge->fresh()->status)->toBe('pending')
        ->and((int) $user->fresh()->negative_balance)->toBe(3000)
        ->and((int) $user->fresh()->total_commission)->toBe(0);

    $deposit = $user->deposits()->create([
        'amount' => 3000, 'cryptocurrency' => 'USDT-TRC20', 'status' => 'pending',
    ]);

    Livewire::actingAs($admin)->test(\App\Livewire\Admin\Payments::class)
        ->call('review', 'Deposit', $deposit->id, true)
        ->assertHasNoErrors();

    expect((int) $user->fresh()->negative_balance)->toBe(0)
        ->and((int) $user->fresh()->balance)->toBe(8000);

    Livewire::actingAs($user->fresh())->test(Start::class)
        ->call('submitTask')
        ->assertDispatched('task-completed');

    expect($merge->fresh()->status)->toBe('approved')
        ->and((int) $user->fresh()->negative_balance)->toBe(0)
        ->and((int) $user->fresh()->total_commission)->toBe(400)
        ->and((int) $user->fresh()->daily_commission)->toBe(400)
        ->and((int) $user->fresh()->balance)->toBe(8400);
});
