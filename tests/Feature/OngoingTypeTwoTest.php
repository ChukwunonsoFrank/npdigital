<?php

use App\Livewire\Admin\OngoingType2;
use App\Livewire\Admin\Users;
use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => freezeTaskOperatingHours());

afterEach(fn () => unfreezeTaskOperatingHours());

function queuedOrder(User $user, array $attributes = []): CompletedTask
{
    return $user->tasks()->create([
        'title' => 'Navy Midi Dress',
        'cost' => '104.00',
        'task_image_path' => '',
        'status' => 'queued',
        'rating' => '0',
        'rating_id' => 'order-'.str()->random(10),
        'available_after_tasks' => 0,
        ...$attributes,
    ]);
}

it('serves the ongoing type 2 page to admins from the user modal', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false,
        'name' => 'Linwood Koehn',
        'membership_level' => 'Normal',
    ]);
    queuedOrder($user);

    $this->actingAs($admin)
        ->get(route('admin.dashboard.users.ongoing-type-2', ['user' => $user->id]))
        ->assertOk()
        ->assertSee('Ongoing Type 2')
        ->assertSee('Assign type 2 merge orders')
        ->assertSee('Linwood Koehn')
        ->assertSee('Navy Midi Dress')
        ->assertSee('Normal order')
        ->assertSee('Add product')
        ->assertSee('Assign as Type 2')
        ->assertDontSee('Clear existing queued and pending orders first');

    $this->actingAs($user)
        ->get(route('admin.dashboard.users.ongoing-type-2'))
        ->assertForbidden();
});

it('opens the ongoing type 2 page from the user management modal', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'name' => 'Daniella Dom']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSee('Ongoing Type 2')
        ->call('openOngoingType2')
        ->assertRedirect(route('admin.dashboard.users.ongoing-type-2', ['user' => $user->id]));
});

it('lists only members with ongoing orders before a member is selected', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $busyUser = User::factory()->create(['is_admin' => false, 'name' => 'Busy Member', 'membership_level' => 'Normal']);
    $idleUser = User::factory()->create(['is_admin' => false, 'name' => 'Idle Member', 'membership_level' => 'Normal']);
    queuedOrder($busyUser);

    $component = Livewire::actingAs($admin)->test(OngoingType2::class)
        ->assertSee('Busy Member')
        ->assertSee('1 ongoing')
        ->assertDontSee('Idle Member');

    $component->call('selectUser', $busyUser->id)
        ->assertSet('userId', $busyUser->id)
        ->assertSee('Navy Midi Dress')
        ->assertDontSee('Idle Member');

    expect($idleUser->tasks()->count())->toBe(0);
});

it('assigns a type 2 merge from products the admin chooses and calculates the amounts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false,
        'membership_level' => 'Normal',
        'balance' => 5000,
        'tasks_completed' => 2,
        'submitted_warehouse_products' => [],
    ]);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '15.00']);

    $products = WarehouseProduct::factory()->count(5)->create([
        'level' => 'normal',
        'day' => 1,
        'group_key' => 'a',
    ]);

    foreach ([100, 200, 300, 400, 500] as $index => $price) {
        $products[$index]->update(['price' => $price]);
    }

    $products[0]->update(['name' => 'Chosen One']);
    $products[1]->update(['name' => 'Submitted Product']);
    $products[2]->update(['name' => 'Chosen Two']);
    $products[3]->update(['name' => 'Chosen Three']);

    $user->update(['submitted_warehouse_products' => [$products[1]->product_id]]);

    $order = queuedOrder($user, [
        'cost' => '1041.00',
        'warehouse_product_id' => $products[0]->product_id,
        'available_after_tasks' => 2,
    ]);

    $component = Livewire::actingAs($admin)->test(OngoingType2::class, ['userId' => $user->id])
        ->assertSee('$156.15') // normal commission at 15% of $1,041.00 stays separate from the merge commission
        ->assertSee('No products selected yet.')
        ->call('addMergeProduct', $order->id, $products[0]->product_id)
        ->assertSet('mergeCommission.'.$order->id, '5.00')
        ->assertSet('negativeBalance.'.$order->id, '50.00')
        ->call('addMergeProduct', $order->id, $products[2]->product_id)
        ->assertSet('mergeCommission.'.$order->id, '20.00')
        ->assertSet('negativeBalance.'.$order->id, '350.00')
        ->call('addMergeProduct', $order->id, $products[3]->product_id)
        ->assertSet('mergeCommission.'.$order->id, '40.00')
        ->assertSet('negativeBalance.'.$order->id, '750.00')
        ->assertSee('Combined $800.00')
        ->call('addMergeProduct', $order->id, $products[0]->product_id)
        ->assertSee('That product is already in a merge bundle.')
        ->call('addMergeProduct', $order->id, $products[1]->product_id)
        ->assertSee('That product has already been submitted by this user.')
        ->call('assignTypeTwo', $order->id)
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Order assigned as Type 2.');

    $order->refresh();

    expect($order->is_merge)->toBeTrue()
        ->and((float) $order->commission_percentage)->toBe(5.0)
        ->and((int) $order->commission_amount)->toBe(4000)
        ->and((int) $order->merge_negative_balance)->toBe(75000)
        ->and((float) $order->cost)->toBe(800.0)
        ->and($order->status)->toBe('queued')
        ->and($order->merge_position)->toBe(1)
        ->and($order->merge_items)->toHaveCount(3)
        ->and(array_column($order->merge_items, 'id'))->toBe([
            $products[0]->product_id,
            $products[2]->product_id,
            $products[3]->product_id,
        ])
        ->and($order->title)->toBe('Chosen One + Chosen Two + 1 more')
        ->and($user->fresh()->negative_balance)->toBe(0);

    $component->assertSee('Type 2 assigned');

    Livewire::actingAs($user->fresh())->test(\App\Livewire\Dashboard\Start::class)
        ->call('startTask')
        ->assertDispatched('open-task-modal')
        ->assertSee('Product optimization order')
        ->assertSee('$800.00');
});

it('keeps manual commission and negative balance overrides as products are added', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false,
        'membership_level' => 'Normal',
        'balance' => 5000,
    ]);
    $products = WarehouseProduct::factory()->count(3)->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);
    $products[0]->update(['price' => 100]);
    $products[1]->update(['price' => 300]);
    $products[2]->update(['price' => 400]);
    $order = queuedOrder($user);

    $component = Livewire::actingAs($admin)->test(OngoingType2::class, ['userId' => $user->id])
        ->call('addMergeProduct', $order->id, $products[0]->product_id)
        ->assertSet('mergeCommission.'.$order->id, '5.00')
        ->assertSet('negativeBalance.'.$order->id, '50.00')
        ->set('mergeCommission.'.$order->id, '156.15')
        ->set('negativeBalance.'.$order->id, '30.00')
        ->call('addMergeProduct', $order->id, $products[1]->product_id)
        ->assertSet('mergeCommission.'.$order->id, '156.15')
        ->assertSet('negativeBalance.'.$order->id, '30.00')
        ->call('addMergeProduct', $order->id, $products[2]->product_id)
        ->assertSet('mergeCommission.'.$order->id, '156.15')
        ->assertSet('negativeBalance.'.$order->id, '30.00')
        ->call('assignTypeTwo', $order->id)
        ->assertHasNoErrors();

    $order->refresh();

    expect((int) $order->commission_amount)->toBe(15615)
        ->and((int) $order->merge_negative_balance)->toBe(3000)
        ->and((float) $order->cost)->toBe(3123.0)
        ->and($order->merge_items)->toHaveCount(3);

    Livewire::actingAs($user->fresh())->test(\App\Livewire\Dashboard\Start::class)
        ->call('startTask')
        ->assertDispatched('open-task-modal')
        ->assertSee('Product optimization order')
        ->assertSee('$3,123.00');
});

it('does not reuse products from another ongoing merge', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    $usedProduct = WarehouseProduct::factory()->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a', 'price' => 100]);
    $availableProduct = WarehouseProduct::factory()->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a', 'price' => 200]);

    queuedOrder($user, [
        'title' => 'Existing merge',
        'is_merge' => true,
        'merge_items' => [['id' => $usedProduct->product_id, 'name' => 'Used Product', 'price' => 100]],
        'commission_percentage' => 5,
        'commission_amount' => 500,
        'merge_negative_balance' => 0,
    ]);
    $order = queuedOrder($user, ['available_after_tasks' => 1]);

    Livewire::actingAs($admin)->test(OngoingType2::class, ['userId' => $user->id])
        ->call('addMergeProduct', $order->id, $usedProduct->product_id)
        ->assertSee('That product is already in a merge bundle.')
        ->assertSet('mergeProducts.'.$order->id, [])
        ->call('addMergeProduct', $order->id, $availableProduct->product_id)
        ->assertSet('mergeProducts.'.$order->id.'.0.id', $availableProduct->product_id);
});

it('applies the type 2 negative balance immediately to a pending order', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false,
        'membership_level' => 'Normal',
        'balance' => 5000,
        'negative_balance' => 500,
    ]);
    $product = WarehouseProduct::factory()->create([
        'level' => 'normal',
        'day' => 1,
        'group_key' => 'a',
        'price' => 1000,
    ]);
    $order = queuedOrder($user, ['status' => 'pending', 'available_after_tasks' => 0]);

    Livewire::actingAs($admin)->test(OngoingType2::class, ['userId' => $user->id])
        ->call('addMergeProduct', $order->id, $product->product_id)
        ->assertSet('mergeCommission.'.$order->id, '50.00')
        ->assertSet('negativeBalance.'.$order->id, '950.00')
        ->set('negativeBalance.'.$order->id, '25.00')
        ->call('assignTypeTwo', $order->id)
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('pending')
        ->and((int) $order->fresh()->merge_negative_balance)->toBe(2500)
        ->and($user->fresh()->negative_balance)->toBe(2500);

    Livewire::actingAs($user->fresh())->test(\App\Livewire\Dashboard\Start::class)
        ->call('submitTask', $order->id)
        ->assertDispatched('insufficient-balance', message: 'Top up $25.00 to submit this merge product.');
});

it('requires a bundle and commission and refuses orders that are already closed or merged', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    $product = WarehouseProduct::factory()->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a', 'price' => 100]);
    $openOrder = queuedOrder($user);
    $approvedOrder = queuedOrder($user, ['status' => 'approved']);
    $mergedOrder = queuedOrder($user, [
        'is_merge' => true,
        'merge_items' => [['id' => 'existing', 'name' => 'Existing merge item', 'price' => 10]],
        'commission_percentage' => 5,
        'commission_amount' => 100,
        'merge_negative_balance' => 0,
    ]);

    $component = Livewire::actingAs($admin)->test(OngoingType2::class, ['userId' => $user->id])
        ->set('mergeCommission.'.$openOrder->id, '0.00')
        ->set('negativeBalance.'.$openOrder->id, '0.00')
        ->call('assignTypeTwo', $openOrder->id)
        ->assertHasErrors('mergeProducts.'.$openOrder->id)
        ->assertSee('Add at least one product to this merge bundle.')
        ->call('addMergeProduct', $openOrder->id, $product->product_id)
        ->set('mergeCommission.'.$openOrder->id, '0.00')
        ->call('assignTypeTwo', $openOrder->id)
        ->assertHasErrors('mergeCommission.'.$openOrder->id);

    expect($openOrder->fresh()->is_merge)->toBeFalse();

    $component->call('assignTypeTwo', $approvedOrder->id)
        ->assertHasErrors('typeTwo.'.$approvedOrder->id);

    $component->call('assignTypeTwo', $mergedOrder->id)
        ->assertHasErrors('typeTwo.'.$mergedOrder->id);

    expect($approvedOrder->fresh()->is_merge)->toBeFalse()
        ->and($mergedOrder->fresh()->commission_amount)->toBe(100);
});
