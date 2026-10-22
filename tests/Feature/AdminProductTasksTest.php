<?php

use App\Livewire\Admin\AdminTasks;
use App\Livewire\Admin\Users;
use App\Models\CompletedTask;
use App\Models\User;
use App\Models\WarehouseExclusiveProduct;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('serves product tasks at the new admin URL and updates navigation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    expect(route('admin.dashboard.tasks', absolute: false))->toBe('/admin/product-tasks');
    $this->actingAs($admin)->get('/admin/product-tasks')->assertOk()
        ->assertSee('Product Tasks')->assertSee('Assignments')->assertSee('Tasks History');
    Livewire::actingAs($admin)->test(AdminTasks::class)->assertSeeLivewire(Users::class);
    $this->get('/admin/dashboard')->assertOk()->assertSee('/admin/product-tasks');
    $this->get('/admin/dashboard/tasks')->assertNotFound();
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/admin/product-tasks')->assertForbidden();
});

it('shows assigned and unassigned members in their account columns and searches names and usernames', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create([
        'name' => 'Real Member', 'username' => 'real-search', 'is_admin' => false,
        'membership_level' => 'Gold', 'has_made_first_deposit' => false,
        'warehouse_groups' => ['gold' => ['day' => 2, 'group' => 'b']],
        'tasks_completed' => 6, 'task_pole' => 40,
    ]);
    User::factory()->create([
        'name' => 'Virtual Member', 'is_admin' => false, 'membership_level' => 'Diamond',
        'is_training' => true, 'has_made_first_deposit' => true,
        'warehouse_groups' => ['diamond' => ['day' => 1, 'group' => 'a']],
    ]);
    User::factory()->create([
        'name' => 'Previous Level Only', 'is_admin' => false, 'membership_level' => 'Gold',
        'warehouse_groups' => ['normal' => ['day' => 1, 'group' => 'a']],
    ]);
    $virtual = User::factory()->create([
        'name' => 'Unassigned Virtual', 'is_admin' => false, 'is_training' => true,
        'warehouse_groups' => null,
    ]);

    Livewire::actingAs($admin)->test(AdminTasks::class)
        ->assertSeeInOrder(['Real Accounts', 'Real Member', 'Previous Level Only', 'Not assigned', 'Virtual Accounts', 'Virtual Member', 'Unassigned Virtual', 'Not assigned'])
        ->assertDontSee($admin->username)
        ->assertSee('Day 2 B')->assertSee('6/40')
        ->assertViewHas('realCount', 2)->assertViewHas('virtualCount', 2)
        ->set('search', 'real-search')->assertSee('Real Member')->assertDontSee('Virtual Member')
        ->assertSee('REAL')->assertDontSee('VIRTUAL')->assertViewHas('realCount', 1)
        ->set('search', 'Virtual Member')->assertSee('Virtual Member')->assertDontSee('Real Member')
        ->assertSee('VIRTUAL')->assertDontSee('REAL')->assertViewHas('virtualCount', 1)
        ->call('selectTab', 'history')->set('search', 'real-search')->assertDontSee(' · training')
        ->set('search', 'Virtual Member')->assertSee(' · training')
        ->call('selectTab', 'assignments')
        ->set('search', 'missing')->assertSee('No real accounts found.')->assertSee('No virtual accounts found.')
        ->set('search', 'Unassigned Virtual')->call('openAssignment', $virtual->id)
        ->assertSet('assignmentUserIds', [(string) $virtual->id])->assertSet('assignmentDay', 1)->assertSet('assignmentGroup', 'a');
});

it('shows approved submission totals and the last submission including members with no submissions', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-20'));
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Submitted Member', 'is_admin' => false]);
    User::factory()->create(['name' => 'Zero Member', 'is_admin' => false]);

    foreach (['approved' => '2026-10-11', 'pending' => '2026-10-13', 'queued' => '2026-10-14'] as $status => $date) {
        $member->tasks()->create([
            'title' => 'Example', 'cost' => '30', 'task_image_path' => 'example.jpg',
            'status' => $status, 'rating' => '5', 'rating_id' => 'example',
        ])->forceFill(['created_at' => '2026-10-01', 'updated_at' => $date])->save();
    }

    Livewire::actingAs($admin)->test(AdminTasks::class)
        ->set('search', 'missing')->call('selectTab', 'history')->assertSet('search', '')
        ->assertSee('Submitted Member')->assertSee('Zero Member')->assertSee('Oct 11, 2026')
        ->assertDontSee('Oct 13, 2026')->assertDontSee('Oct 14, 2026')
        ->assertViewHas('members', fn ($members) => $members->first()->submissions_count === 1)
        ->set('search', 'Zero Member')->assertDontSee('Submitted Member')
        ->assertViewHas('members', fn ($members) => $members->first()->submissions_count === 0 && $members->first()->last_submitted_at === null)
        ->set('search', 'missing')->assertSee('No members found.');
});

it('resets history pagination when searching or changing tabs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(21)->create(['is_admin' => false, 'name' => 'History Member']);

    Livewire::actingAs($admin)->test(AdminTasks::class)
        ->call('selectTab', 'history')->call('setPage', 2)
        ->set('search', 'History Member')->assertSet('paginators.page', 1)
        ->call('setPage', 2)->call('selectTab', 'assignments')->assertSet('paginators.page', 1);
});

it('creates and edits warehouse assignments without losing other levels or task progress', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'name' => 'Assigned Member', 'membership_level' => 'Normal',
        'tasks_completed' => 7, 'warehouse_groups' => ['gold' => ['day' => 3, 'group' => 'a']],
    ]);
    WarehouseProduct::factory()->create(['day' => 2, 'group_key' => 'b']);

    Livewire::actingAs($admin)->test(AdminTasks::class)
        ->call('openAssignment')->set('assignmentUserIds', [(string) $user->id])->call('nextAssignmentStep')
        ->set('assignmentSelection', '2-b')->call('nextAssignmentStep')->call('saveAssignment')
        ->assertHasNoErrors()->assertSet('showAssignmentModal', false)->assertSee('Assigned Member')
        ->assertDispatched('toast', message: 'Products assigned successfully.')
        ->assertDontSee('Products assigned successfully.')
        ->call('openAssignment', $user->id)->assertSet('assignmentDay', 2)->assertSet('assignmentGroup', 'b');

    expect($user->fresh()->warehouse_groups)->toBe([
        'gold' => ['day' => 3, 'group' => 'a'], 'normal' => ['day' => 2, 'group' => 'b'],
    ])->and($user->fresh()->tasks_completed)->toBe(7);
});

it('rejects invalid users, days, groups and empty or exhausted assignments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal']);
    $product = WarehouseProduct::factory()->create();
    $user->update(['submitted_warehouse_products' => [$product->product_id]]);

    Livewire::actingAs($admin)->test(AdminTasks::class)
        ->call('openAssignment')->set('assignmentUserIds', [(string) $admin->id])->call('saveAssignment')
        ->assertHasErrors('assignmentUserIds.0')
        ->set('assignmentUserIds', [(string) $user->id])->set('assignmentSelection', '6-c')
        ->call('saveAssignment')->assertHasErrors(['assignmentDay', 'assignmentGroup'])
        ->set('assignmentSelection', '1-a')->call('saveAssignment')
        ->assertHasErrors('assignmentSelection')
        ->set('assignmentSelection', '1-b')->call('saveAssignment')->assertHasErrors('assignmentSelection');

    expect($user->fresh()->warehouseGroup())->toBeNull();
});

it('opens the task account summary and reuses the premium task flow for either account type', function (bool $isTraining) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'is_training' => $isTraining, 'name' => 'Task Member',
        'membership_level' => 'Gold', 'balance' => 110321,
        'lien_status' => 'on_hold', 'lien_amount' => 4321,
        'tasks_completed' => 5, 'task_pole' => 40,
    ]);
    $product = WarehouseExclusiveProduct::factory()->create([
        'level' => 'gold', 'name' => 'Gold Premium', 'name2' => null, 'price' => 35, 'price2' => null,
    ]);
    WarehouseExclusiveProduct::factory()->create(['level' => 'diamond', 'name' => 'Wrong Level']);

    $component = Livewire::actingAs($admin)->test(Users::class, ['taskSummary' => true])
        ->assertDontSee('User Management')->assertDontSee('role="dialog"', false)
        ->dispatch('show-task-user', userId: $user->id)
        ->assertSee($isTraining ? 'Virtual/Training' : 'Real Account')
        ->assertSee('VIP Level 3')->assertSee('$1,103.21')->assertSee('$43.21')->assertSee('5 / 40')
        ->assertSee('Premium Tasks')->assertSee('Clear Negative')
        ->call('openAddExclusive')->assertSee('Add Premium Task')
        ->assertSee('Gold Premium')->assertDontSee('Wrong Level')
        ->set('exclusiveProductId', $product->exclusive_id)
        ->set('exclusivePrice', '43.20')->set('exclusiveCommission', '7.5')
        ->call('assignExclusive')->assertHasNoErrors()
        ->assertSet('showAddExclusive', false)->assertSet('selectedUserId', $user->id);

    $task = CompletedTask::query()->where('user_id', $user->id)->sole();
    expect($task->title)->toBe('Gold Premium')
        ->and($task->cost)->toBe('43.20')
        ->and((float) $task->commission_percentage)->toBe(7.5)
        ->and($task->brand)->toBe($product->brand)
        ->and($task->status)->toBe('queued');

    $component->call('openAddExclusive')->call('closeAddExclusive')
        ->assertSet('selectedUserId', $user->id)
        ->call('closeUserDetails')->assertDontSee('role="dialog"', false)
        ->dispatch('show-task-user', userId: $admin->id)->assertSet('selectedUserId', null);
})->with([false, true]);

it('rejects non admins opening a task account summary', function () {
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($user)->test(Users::class, ['taskSummary' => true])
        ->assertForbidden();
});

it('assigns a customized premium combo to multiple accounts through all three steps', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $real = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal', 'tasks_completed' => 3, 'balance' => 12345, 'referral_code' => 'find-real']);
    $virtual = User::factory()->create(['is_admin' => false, 'is_training' => true, 'membership_level' => 'Normal', 'tasks_completed' => 6]);
    WarehouseProduct::factory()->count(3)->create(['day' => 2, 'group_key' => 'b']);
    $premium = WarehouseExclusiveProduct::factory()->create(['name' => 'Tea Sampler', 'name2' => 'Kitchen Set', 'price' => 35, 'price2' => 42]);

    $component = Livewire::actingAs($admin)->test(AdminTasks::class)->call('openAssignment')
        ->assertSee('Step 1: Select Users')->call('nextAssignmentStep')->assertHasErrors('assignmentUserIds')
        ->set('assignmentSearch', 'find-real')->assertViewHas('assignmentUsers', fn ($users) => $users->pluck('id')->all() === [$real->id])
        ->set('assignmentUserIds', [(string) $real->id, (string) $virtual->id])->assertSee('$123.45')
        ->call('nextAssignmentStep')->assertSee('Step 2: Select Group')
        ->call('nextAssignmentStep')->assertHasErrors('assignmentSelection')
        ->set('assignmentSelection', '2-b')->call('nextAssignmentStep')->assertSee('Step 3: Premium Product (Optional)')
        ->set('addPremium', true)->set('premiumProductId', $premium->exclusive_id)
        ->assertSet('premiumPrice', '35')->assertSet('premiumPrice2', '42')
        ->set('premiumPrice', '41.25')->set('premiumPrice2', '19.75')->set('premiumCommission', '7.5')->set('premiumPosition', 3)
        ->assertSee('Combined: $61.00')->assertSee('Total Profit: $4.58')
        ->call('previousAssignmentStep')->assertSee('Day 2 B')->call('nextAssignmentStep')
        ->call('saveAssignment')->assertHasNoErrors()->assertSet('showAssignmentModal', false);

    foreach ([$real, $virtual] as $user) {
        expect($user->fresh()->warehouseGroup())->toBe(['day' => 2, 'group' => 'b']);
        $task = $user->tasks()->sole();
        expect($task->title)->toBe('Tea Sampler + Kitchen Set')->and($task->cost)->toBe('61.00')
            ->and((float) $task->commission_percentage)->toBe(7.5)
            ->and((int) $task->available_after_tasks)->toBe($user->tasks_completed + 2)
            ->and($task->brand)->toBe($premium->brand);
    }

    $component->call('openAssignment')->assertSet('assignmentUserIds', [])->assertSet('addPremium', false)->assertSet('assignmentStep', 1);
});

it('rejects mixed levels and invalid premium customization without partially assigning users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $first = User::factory()->create(['is_admin' => false, 'membership_level' => 'Normal', 'tasks_completed' => 0, 'task_pole' => 40]);
    $second = User::factory()->create(['is_admin' => false, 'membership_level' => 'Gold', 'tasks_completed' => 39, 'task_pole' => 40]);
    WarehouseProduct::factory()->count(3)->create();
    $premium = WarehouseExclusiveProduct::factory()->create();
    $wrongLevel = WarehouseExclusiveProduct::factory()->create(['level' => 'gold']);

    $component = Livewire::actingAs($admin)->test(AdminTasks::class)->call('openAssignment')
        ->set('assignmentUserIds', [(string) $first->id, (string) $second->id])->call('nextAssignmentStep')->assertHasErrors('assignmentUserIds');
    $second->update(['membership_level' => 'Normal']);
    $component->call('nextAssignmentStep')->set('assignmentSelection', '1-a')->call('nextAssignmentStep')
        ->set('addPremium', true)->set('premiumProductId', $wrongLevel->exclusive_id)->call('saveAssignment')->assertHasErrors('premiumProductId')
        ->set('premiumProductId', $premium->exclusive_id)->set('premiumPrice2', '-1')->set('premiumCommission', '101')
        ->call('saveAssignment')->assertHasErrors(['premiumPrice2', 'premiumCommission'])
        ->set('premiumPrice2', '20')->set('premiumCommission', '5')->set('premiumPosition', 2)
        ->call('saveAssignment')->assertHasErrors('premiumPosition');

    expect($first->fresh()->warehouseGroup())->toBeNull()->and($first->tasks()->count())->toBe(0);
    $component->set('addPremium', false)->call('saveAssignment')->assertHasNoErrors();
    expect($first->tasks()->count())->toBe(0)->and($second->fresh()->warehouseGroup())->toBe(['day' => 1, 'group' => 'a']);
});
