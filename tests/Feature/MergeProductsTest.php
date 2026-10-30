<?php

use App\Livewire\Admin\AssignOrders;
use App\Livewire\Dashboard\Record;
use App\Livewire\Dashboard\Start;
use App\Models\CompletedTask;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the assign orders page for admins only', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'name' => 'Task Member']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard.assign-orders', ['user' => $user->id]))
        ->assertOk()
        ->assertSee('Assign Orders')
        ->assertSee('Assign task orders')
        ->assertSee('Task Member');

    $this->actingAs($user)
        ->get(route('admin.dashboard.assign-orders'))
        ->assertForbidden();
});

it('auto assigns a full set of random products from the user level', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 100000,
        'tasks_completed' => 0, 'products_per_set' => null,
    ]);
    $products = WarehouseProduct::factory()->count(35)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->assertSet('totalOrders', 30)
        ->call('autoAssign')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $tasks = CompletedTask::query()->where('user_id', $user->id)->orderBy('id')->get();

    expect($tasks)->toHaveCount(30)
        ->and($tasks->every(fn (CompletedTask $task): bool => $task->status === 'queued' && ! $task->is_merge))->toBeTrue()
        ->and($tasks->pluck('warehouse_product_id')->unique())->toHaveCount(30)
        ->and($tasks->pluck('warehouse_product_id')->diff($products->pluck('product_id')))->toBeEmpty()
        ->and($tasks->pluck('available_after_tasks')->all())->toBe(range(0, 29))
        ->and($user->fresh()->productsPerSet())->toBe(30);
});

it('increases or reduces the set from the normal thirty orders', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 100000, 'tasks_completed' => 0,
    ]);
    WarehouseProduct::factory()->count(45)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 5)
        ->call('autoAssign')
        ->assertHasNoErrors();

    expect(CompletedTask::query()->where('user_id', $user->id)->count())->toBe(5)
        ->and($user->fresh()->productsPerSet())->toBe(5);

    $component->set('totalOrders', 40)
        ->call('autoAssign')
        ->assertHasNoErrors();

    expect(CompletedTask::query()->where('user_id', $user->id)->count())->toBe(40)
        ->and($user->fresh()->productsPerSet())->toBe(40);
});

it('builds merge bundles from admin selected products and calculates their amounts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 5000, 'tasks_completed' => 2,
    ]);

    $bundleProducts = collect([
        ['name' => 'Bundle Alpha Chair', 'price' => 100],
        ['name' => 'Bundle Beta Lamp', 'price' => 40],
        ['name' => 'Bundle Gamma Desk', 'price' => 20],
        ['name' => 'Bundle Delta Sofa', 'price' => 200],
        ['name' => 'Bundle Epsilon Rug', 'price' => 60],
        ['name' => 'Bundle Zeta Shelf', 'price' => 40],
    ])->map(fn (array $product): WarehouseProduct => WarehouseProduct::factory()->create([
        ...$product,
        'level' => 'normal',
        'day' => 2,
        'group_key' => 'b',
    ]));

    WarehouseProduct::factory()->count(34)->create(['level' => 'normal', 'day' => 2, 'group_key' => 'b']);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 12);

    expect($component->get('totalOrders'))->toBe(12);

    $component->call('addMergeOrder')
        ->assertSee('No products selected yet.')
        ->set('productSearch', 'Bundle Alpha')
        ->assertSee('Bundle Alpha Chair')
        ->assertDontSee('Bundle Beta Lamp')
        ->call('addMergeProduct', 0, $bundleProducts[0]->product_id)
        ->call('addMergeProduct', 0, $bundleProducts[1]->product_id)
        ->call('addMergeProduct', 0, $bundleProducts[2]->product_id)
        ->call('addMergeOrder')
        ->call('addMergeProduct', 1, $bundleProducts[3]->product_id)
        ->call('addMergeProduct', 1, $bundleProducts[4]->product_id)
        ->call('addMergeProduct', 1, $bundleProducts[5]->product_id)
        ->assertCount('mergeOrders', 2)
        ->set('mergeOrders.0.position', 3)
        ->set('mergeOrders.1.position', 8)
        ->call('autoAssign')
        ->assertHasNoErrors();

    $tasks = CompletedTask::query()->where('user_id', $user->id)->orderBy('id')->get();
    $merges = $tasks->where('is_merge', true)->values();

    expect($tasks)->toHaveCount(12)
        ->and($tasks->where('is_merge', false))->toHaveCount(10)
        ->and($merges)->toHaveCount(2)
        ->and($merges->pluck('merge_position')->all())->toBe([3, 8])
        ->and($merges->pluck('available_after_tasks')->all())->toBe([4, 9])
        ->and(array_column($merges[0]->merge_items, 'id'))->toBe($bundleProducts->take(3)->pluck('product_id')->all())
        ->and(array_column($merges[1]->merge_items, 'id'))->toBe($bundleProducts->skip(3)->pluck('product_id')->all())
        ->and((float) $merges[0]->cost)->toBe(160.0)
        ->and((int) $merges[0]->commission_amount)->toBe(800)
        ->and((int) $merges[0]->merge_negative_balance)->toBe(11000)
        ->and((float) $merges[1]->cost)->toBe(300.0)
        ->and((int) $merges[1]->commission_amount)->toBe(1500)
        ->and((int) $merges[1]->merge_negative_balance)->toBe(25000);

    expect($user->fresh()->productsPerSet())->toBe(14);
});

it('defaults to thirty orders when the current set is already complete', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'products_per_set' => 5, 'tasks_completed' => 5,
    ]);

    Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->assertSet('totalOrders', 30);
});

it('will not add more merge orders than the total orders', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    WarehouseProduct::factory()->count(12)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 2)
        ->call('addMergeOrder')
        ->call('addMergeOrder')
        ->call('addMergeOrder')
        ->assertHasErrors('mergeOrders');

    expect($component->get('mergeOrders'))->toHaveCount(2);
});

it('requires at least one product in every merge bundle', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    WarehouseProduct::factory()->count(6)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 2)
        ->call('addMergeOrder')
        ->call('autoAssign')
        ->assertHasErrors('mergeOrders.0.items');

    expect(CompletedTask::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('will not put the same product in two merge bundles', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    $products = WarehouseProduct::factory()->count(6)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 3)
        ->call('addMergeOrder')
        ->call('addMergeOrder')
        ->call('addMergeProduct', 0, $products[0]->product_id)
        ->assertHasNoErrors()
        ->call('addMergeProduct', 1, $products[0]->product_id)
        ->assertHasErrors('productSearch')
        ->assertSee('That product is already in a merge bundle.');

    expect($component->get('mergeOrders.1.items'))->toBeEmpty();
});

it('lets admins choose bundle products from any warehouse level', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    WarehouseProduct::factory()->count(6)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);
    $silverProduct = WarehouseProduct::factory()->create([
        'name' => 'Silver Only Item', 'level' => 'silver', 'day' => 1, 'group_key' => 'a',
    ]);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 2)
        ->call('addMergeOrder')
        ->set('productSearch', 'Silver Only')
        ->assertSee('Silver Only Item')
        ->assertSee('Silver')
        ->call('addMergeProduct', 0, $silverProduct->product_id)
        ->assertHasNoErrors()
        ->call('addMergeProduct', 0, 'missing-product')
        ->assertHasErrors('productSearch')
        ->assertSee('That product is no longer available in the warehouse.');

    expect($component->get('mergeOrders.0.items'))->toHaveCount(1)
        ->and($component->get('mergeOrders.0.items.0.id'))->toBe($silverProduct->product_id)
        ->and($component->get('mergeOrders.0.items.0.level'))->toBe('silver');
});

it('rejects duplicate or out of range merge positions', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    $products = WarehouseProduct::factory()->count(12)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 5)
        ->call('addMergeOrder')
        ->call('addMergeProduct', 0, $products[0]->product_id)
        ->call('addMergeOrder')
        ->call('addMergeProduct', 1, $products[1]->product_id)
        ->set('mergeOrders.0.position', 2)
        ->set('mergeOrders.1.position', 2)
        ->call('autoAssign')
        ->assertHasErrors('mergeOrders.1.position');

    $component->set('mergeOrders.1.position', 7)
        ->call('autoAssign')
        ->assertHasErrors('mergeOrders.1.position');

    expect(CompletedTask::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('requires existing open orders to be cleared before assigning', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    $existing = $user->tasks()->create([
        'title' => 'Old queued order', 'cost' => '20.00', 'task_image_path' => '',
        'status' => 'queued', 'rating' => '0', 'rating_id' => 'old-queued',
    ]);
    WarehouseProduct::factory()->count(30)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('clearExisting', false)
        ->call('autoAssign')
        ->assertHasErrors('clearExisting');

    expect(CompletedTask::query()->find($existing->id))->not->toBeNull();

    $component->set('clearExisting', true)
        ->call('autoAssign')
        ->assertHasNoErrors();

    expect(CompletedTask::query()->find($existing->id))->toBeNull()
        ->and(CompletedTask::query()->where('user_id', $user->id)->count())->toBe(30);
});

it('applies the automatically calculated merge negative balance when the order starts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 10000,
        'has_made_first_deposit' => true, 'tasks_completed' => 0,
    ]);
    $bundleProduct = WarehouseProduct::factory()->create([
        'name' => 'Merge Top Up Item', 'price' => 175, 'level' => 'normal', 'day' => 1, 'group_key' => 'a',
    ]);
    WarehouseProduct::factory()->count(5)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 2)
        ->call('addMergeOrder')
        ->call('addMergeProduct', 0, $bundleProduct->product_id)
        ->set('mergeOrders.0.position', 1)
        ->assertSee('$8.75')
        ->assertSee('-$75.00')
        ->call('autoAssign')
        ->assertHasNoErrors();

    $merge = CompletedTask::query()->where('user_id', $user->id)->where('is_merge', true)->first();

    expect($merge)->not->toBeNull()
        ->and((int) $merge->commission_amount)->toBe(875)
        ->and((int) $merge->merge_negative_balance)->toBe(7500)
        ->and((float) $merge->cost)->toBe(175.0);

    Livewire::actingAs($user->fresh())->test(Start::class)
        ->call('startTask')
        ->assertDispatched('open-task-modal')
        ->assertSee('Merged order');

    expect((int) $user->fresh()->negative_balance)->toBe(7500);
});

it('uses the fixed merge commission when the merge order is submitted', function () {
    Notification::fake();
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 10000,
        'has_made_first_deposit' => true, 'tasks_completed' => 0, 'negative_balance' => 0,
    ]);
    $merge = $user->tasks()->create([
        'title' => 'Fixed Commission Merge', 'cost' => '300.00', 'task_image_path' => '',
        'commission_percentage' => 5, 'commission_amount' => 2500, 'status' => 'pending',
        'rating' => '0', 'rating_id' => 'fixed-commission', 'is_merge' => true,
        'merge_items' => [['id' => 'one', 'name' => 'Fixed Commission Item', 'price' => 300]],
        'merge_position' => 1, 'available_after_tasks' => 0,
    ]);

    expect($merge->profit())->toBe(25.0);

    Livewire::actingAs($user)->test(Record::class)
        ->call('submitTask', $merge->id)
        ->assertDispatched('task-completed');

    expect($merge->fresh()->status)->toBe('approved')
        ->and((int) $user->fresh()->total_commission)->toBe(2500)
        ->and((int) $user->fresh()->daily_commission)->toBe(2500);
});

it('removes a queued or pending order and clears a pending merge shortfall', function () {
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

    $component = Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->call('removeOrder', $queued->id)
        ->assertDispatched('toast', message: 'Order removed.');

    expect(CompletedTask::query()->find($queued->id))->toBeNull()
        ->and($user->fresh()->negative_balance)->toBe(3000);

    $component->call('removeOrder', $pending->id);

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
        'title' => 'First Merge Item + Second Merge Item', 'cost' => '80.00', 'task_image_path' => '',
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
        ->assertDispatched('open-task-modal')
        ->assertSee('Product optimization order')
        ->assertSee('First Merge Item')
        ->assertSee('Second Merge Item')
        ->assertSee('Product value')
        ->assertSee('$4.00')
        ->assertSee('Product profit')
        ->assertSee('-$30.00')
        ->assertSee('Merged order')
        ->assertSee('Submit product')
        ->assertSeeInOrder(['Merged order', 'Submit product'])
        ->assertDontSee('Top Up Wallet')
        ->assertDontSee('Submit Task');

    expect($user->fresh()->negative_balance)->toBe(3000)
        ->and($merge->fresh()->status)->toBe('pending');

    $component->call('submitTask')
        ->assertDispatched('insufficient-balance', message: 'Top up $30.00 to submit this merge product.');

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

it('shows the merged order notice and submits an affordable merge product', function () {
    Notification::fake();
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 10000,
        'training_balance' => 0, 'has_made_first_deposit' => true,
        'tasks_completed' => 0, 'task_batch' => 0, 'negative_balance' => 0,
    ]);
    $user->tasks()->create([
        'title' => 'Covered Merge', 'cost' => '80.00', 'task_image_path' => '',
        'commission_percentage' => 5, 'status' => 'queued', 'rating' => '0',
        'rating_id' => 'covered-merge', 'is_merge' => true,
        'merge_items' => [
            ['id' => 'one', 'name' => 'Covered Item One', 'price' => 40],
            ['id' => 'two', 'name' => 'Covered Item Two', 'price' => 40],
        ],
        'merge_position' => 3, 'available_after_tasks' => 0,
    ]);

    Livewire::actingAs($user)->test(Start::class)
        ->call('startTask')
        ->assertDispatched('open-task-modal')
        ->assertSee('Product optimization order')
        ->assertSee('Covered Merge')
        ->assertSee('Task 3 / 30')
        ->assertSee('Product value')
        ->assertSee('$80.00')
        ->assertSee('Product profit')
        ->assertSee('+$4.00')
        ->assertSee('Merged order')
        ->assertSee('Submit product')
        ->assertSeeInOrder(['Merged order', 'Submit product'])
        ->assertDontSee('Top Up Wallet');

    expect($user->fresh()->negative_balance)->toBe(0);
});

it('shows the merge shortfall when opening a pending merge from the records page', function () {
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 5000,
        'training_balance' => 0, 'has_made_first_deposit' => true,
        'tasks_completed' => 0, 'task_batch' => 0, 'negative_balance' => 3000,
    ]);
    $merge = $user->tasks()->create([
        'title' => 'Pending Merge', 'cost' => '80.00', 'task_image_path' => '',
        'commission_percentage' => 5, 'status' => 'pending', 'rating' => '0',
        'rating_id' => 'pending-merge-modal', 'is_merge' => true,
        'merge_items' => [['id' => 'one', 'name' => 'Pending Item', 'price' => 80]],
        'merge_position' => 2, 'available_after_tasks' => 0,
    ]);

    Livewire::actingAs($user)->test(Record::class)
        ->assertSeeHtml('open-task-modal-'.$merge->id)
        ->assertSee('Product optimization order')
        ->assertSee('Negative balance')
        ->assertSee('-$30.00')
        ->assertSee('Merged order')
        ->assertSee('Submit product')
        ->assertSeeInOrder(['Merged order', 'Submit product'])
        ->assertDontSee('Top Up Wallet')
        ->assertDontSee('Submit Task')
        ->call('submitTask', $merge->id)
        ->assertDispatched('insufficient-balance', message: 'Top up $30.00 to submit this merge product.');

    expect($merge->fresh()->status)->toBe('pending')
        ->and((int) $user->fresh()->negative_balance)->toBe(3000);
});
