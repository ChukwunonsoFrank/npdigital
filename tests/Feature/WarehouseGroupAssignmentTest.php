<?php

use App\Livewire\Admin\Users;
use App\Livewire\Dashboard\Start;
use App\Models\MembershipLevel;
use App\Models\Task;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('delivers only unsubmitted products in the assigned level and group and disables a finished group', function (string $level) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => ucfirst($level), 'balance' => 100000, 'training_balance' => 0,
        'has_made_first_deposit' => true, 'task_batch' => 0, 'tasks_completed' => 0,
    ]);
    MembershipLevel::create(['name' => ucfirst($level), 'percentage' => 0.5]);
    $first = WarehouseProduct::factory()->create(['level' => $level, 'day' => 3, 'group_key' => 'b', 'name' => 'First assigned product', 'price' => 31.20]);
    $second = WarehouseProduct::factory()->create(['level' => $level, 'day' => 3, 'group_key' => 'b', 'name' => 'Second assigned product', 'price' => 24.60]);
    WarehouseProduct::factory()->create(['level' => $level, 'day' => 3, 'group_key' => 'a', 'name' => 'Wrong group']);
    WarehouseProduct::factory()->create(['level' => $level, 'day' => 2, 'group_key' => 'b', 'name' => 'Wrong day']);
    WarehouseProduct::factory()->create(['level' => $level === 'normal' ? 'gold' : 'normal', 'day' => 3, 'group_key' => 'b', 'name' => 'Wrong level']);
    WarehouseProduct::factory()->create(['level' => $level, 'day' => 3, 'group_key' => 'b', 'is_deleted' => true, 'name' => 'Deleted product']);
    Task::create(['title' => 'Legacy fallback', 'cost' => 1, 'task_image_path' => '', 'task_level' => ucfirst($level)]);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)->call('openSetProducts')
        ->assertSet('showSetProducts', true)->assertSee('Assign warehouse group')
        ->call('assignWarehouseGroup', 3, 'b')->assertHasNoErrors()
        ->assertDontSee('Assign warehouse group')->assertDontSee('User Details')
        ->call('showUserDetails', $user->id)->assertSee('Day 3 · Group B');

    foreach ([$first, $second] as $product) {
        $component = Livewire::actingAs($user->fresh())->test(Start::class)
            ->call('startTask')->assertSee($product->name)->assertSet('showTaskModal', true);
        $pending = $user->tasks()->where('status', 'pending')->sole();
        expect($pending->warehouse_product_id)->toBe($product->product_id)
            ->and((float) $pending->cost)->toBe($product->price);

        Livewire::actingAs($user->fresh())->test(Start::class)
            ->call('startTask')->assertRedirect(route('dashboard.record'));
        expect($user->tasks()->where('status', 'pending')->count())->toBe(1);

        $component->call('submitTask');
        expect($pending->fresh()->status)->toBe('approved');

        if ($product->is($first)) {
            Livewire::actingAs($admin)->test(Users::class)
                ->call('showUserDetails', $user->id)->call('openSetProducts')->assertSee('1 remaining')
                ->call('assignWarehouseGroup', 3, 'b')->assertHasNoErrors();
        }
    }

    Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask')
        ->assertSet('showTaskModal', false)->assertDispatched('task-limit-reached');
    expect($user->tasks()->count())->toBe(2)
        ->and($user->fresh()->submitted_warehouse_products)->toBe([$first->product_id, $second->product_id]);

    $component = Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)->call('openSetProducts')->assertSee('Fully submitted')
        ->call('assignWarehouseGroup', 2, 'b')->assertHasNoErrors()
        ->call('showUserDetails', $user->id)->call('openSetProducts')
        ->call('assignWarehouseGroup', 3, 'b')->assertHasErrors(['warehouseGroup'])
        ->assertSee('Assign warehouse group')->assertSee('User Details');
    expect($user->fresh()->warehouseGroup())->toBe(['day' => 2, 'group' => 'b']);

    $component->call('clearRecord')->call('showUserDetails', $user->id)->call('openSetProducts')
        ->call('assignWarehouseGroup', 3, 'b')->assertHasErrors(['warehouseGroup']);
})->with(['normal', 'silver', 'gold', 'platinum', 'diamond']);

it('preserves current tasks and exclusive priority when switching assigned groups', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => 'Normal', 'balance' => 100000, 'training_balance' => 0,
        'has_made_first_deposit' => true, 'task_batch' => 0, 'tasks_completed' => 0,
        'warehouse_groups' => ['normal' => ['day' => 1, 'group' => 'a']],
    ]);
    WarehouseProduct::factory()->create(['name' => 'Original group product', 'image_url' => '/original.jpg']);
    $next = WarehouseProduct::factory()->create(['day' => 4, 'group_key' => 'b', 'name' => 'New group product']);
    $current = Livewire::actingAs($user)->test(Start::class)->call('startTask')->assertSee('Original group product');
    $pending = $user->tasks()->sole();
    $exclusive = $user->tasks()->create([
        'title' => 'Queued exclusive', 'cost' => '10.00', 'commission_percentage' => 5,
        'task_image_path' => '', 'rating' => '0', 'rating_id' => 'exclusive', 'status' => 'queued',
    ]);

    Livewire::actingAs($admin)->test(Users::class)->call('showUserDetails', $user->id)
        ->call('openSetProducts')->call('assignWarehouseGroup', 4, 'b')->assertHasNoErrors();
    expect($pending->fresh()->status)->toBe('pending');
    $this->actingAs($user->fresh());
    $current->call('submitTask');
    Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask')
        ->assertSee('Queued exclusive')->call('submitTask');
    expect($exclusive->fresh()->status)->toBe('approved');
    Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask')->assertSee('New group product');
    expect($user->tasks()->where('status', 'pending')->sole()->warehouse_product_id)->toBe($next->product_id);
});

it('delivers a positioned premium after the preceding warehouse products', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal', 'balance' => 100000, 'training_balance' => 0,
        'has_made_first_deposit' => true, 'task_batch' => 0, 'tasks_completed' => 4,
        'warehouse_groups' => ['normal' => ['day' => 1, 'group' => 'a']],
    ]);
    $regular = WarehouseProduct::factory()->create(['name' => 'Preceding regular product']);
    $premium = $user->tasks()->create([
        'title' => 'Positioned premium', 'cost' => '10.00', 'commission_percentage' => 5,
        'task_image_path' => '', 'rating' => '0', 'rating_id' => 'positioned', 'status' => 'queued',
        'available_after_tasks' => 5,
    ]);

    Livewire::actingAs($user)->test(Start::class)->call('startTask')->assertSee($regular->name)->call('submitTask');
    expect($premium->fresh()->status)->toBe('queued')->and($user->fresh()->tasks_completed)->toBe(5);
    Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask')->assertSee('Positioned premium')->call('submitTask');
    expect($premium->fresh()->status)->toBe('approved');
});

it('keeps assignments separate by level and does not consume unaffordable products', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => 'Gold', 'balance' => 3119, 'training_balance' => 0, 'has_made_first_deposit' => true,
        'warehouse_groups' => ['normal' => ['day' => 2, 'group' => 'a']],
    ]);
    $product = WarehouseProduct::factory()->create(['level' => 'gold', 'day' => 5, 'group_key' => 'b', 'price' => 31.20]);
    Livewire::actingAs($admin)->test(Users::class)->call('showUserDetails', $user->id)->call('openSetProducts')
        ->call('assignWarehouseGroup', 5, 'b')->assertHasNoErrors();
    expect($user->fresh()->warehouse_groups)->toBe([
        'normal' => ['day' => 2, 'group' => 'a'], 'gold' => ['day' => 5, 'group' => 'b'],
    ]);
    Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask')->assertDispatched('insufficient-balance');
    expect($user->tasks()->count())->toBe(0);
    $user->update(['balance' => 3120]);
    Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask')->assertSet('showTaskModal', true);
    expect($user->tasks()->sole()->warehouse_product_id)->toBe($product->product_id);
});

it('validates group coordinates and assigns a populated group', function (int $day, string $group, bool $invalid) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Normal']);
    $component = Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)->call('openSetProducts')->call('assignWarehouseGroup', $day, $group);

    if ($invalid) {
        $component->assertStatus(422);
        expect($user->fresh()->warehouse_groups)->toBeNull();

        return;
    }

    $component->assertHasNoErrors();
    expect($user->fresh()->warehouseGroup())->toBe(['day' => 1, 'group' => 'a']);
})->with([[0, 'a', true], [6, 'b', true], [1, 'c', true], [1, 'a', false]]);

it('forbids non admins from opening or assigning warehouse groups', function () {
    $user = User::factory()->create(['is_admin' => false]);
    Livewire::actingAs($user)->test(Users::class)->call('openSetProducts')->assertForbidden();
    Livewire::actingAs($user)->test(Users::class)->call('assignWarehouseGroup', 1, 'a')->assertForbidden();
});
