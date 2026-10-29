<?php

use App\Livewire\Admin\Users;
use App\Models\Alert;
use App\Models\CompletedTask;
use App\Models\Deposit;
use App\Models\GoldenEgg;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('labels accounts by their training flag rather than deposit status', function (bool $isTraining) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false, 'is_training' => $isTraining,
        'has_made_first_deposit' => $isTraining,
    ]);

    Livewire::actingAs($admin)->test(Users::class)
        ->assertSee($isTraining ? 'VIRTUAL' : 'REAL')
        ->assertDontSee($isTraining ? 'REAL' : 'VIRTUAL')
        ->call('showUserDetails', $user->id)
        ->assertSee($isTraining ? 'Virtual Account' : 'Real Account')
        ->assertDontSee($isTraining ? 'Real Account' : 'Virtual Account');
})->with([false, true]);

it('serves the redesigned user management page at the admin users route', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'membership_level' => 'Diamond',
    ]);
    User::factory()->create(['name' => 'Lina']);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('User Management')
        ->assertSee('Daniella Dom')
        ->assertSee('Search users...');
});

it('filters users by their profile details', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $matchingUser = User::factory()->create(['name' => 'Daniella Dom']);
    $otherUser = User::factory()->create(['name' => 'Lina']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->set('query', 'Daniella')
        ->assertSee($matchingUser->name)
        ->assertDontSee($otherUser->name);
});

it('opens and closes a selected user details modal without exposing their transaction password', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'unhashed_withdrawal_password' => '00000000',
        'balance' => 1000,
        'total_commission' => 250,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSet('selectedUserId', $user->id)
        ->assertSee('User Details')
        ->assertSee('Viewing profile for Daniella Dom')
        ->assertSee('$10.00')
        ->assertSee('Assign Orders')
        ->assertSee('Delete Account')
        ->assertDontSee('00000000')
        ->call('closeUserDetails')
        ->assertSet('selectedUserId', null)
        ->assertDontSee('User Details');
});

it('opens the edit modal prefilled with the selected user details', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'username' => 'Simplicity',
        'email' => 'simplicitytess211@gmail.com',
        'mobile_number' => '+19862526802',
        'unhashed_password' => 'logmein',
        'unhashed_withdrawal_password' => 'paymeout',
        'credit_score' => 100,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('editUser')
        ->assertSet('selectedUserId', null)
        ->assertSet('editingUserId', $user->id)
        ->assertSet('editName', 'Daniella Dom')
        ->assertSet('editUsername', 'Simplicity')
        ->assertSet('editEmail', 'simplicitytess211@gmail.com')
        ->assertSet('editMobileNumber', '+19862526802')
        ->assertSet('editPassword', 'logmein')
        ->assertSet('editTransactionPassword', 'paymeout')
        ->assertSet('editCreditScore', '100')
        ->assertSee('Edit User')
        ->assertSee("Editing Daniella Dom's account details", false)
        ->call('closeEditUser')
        ->assertSet('editingUserId', null);
});

it('saves edited profile details and only rewrites passwords that were changed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'password' => Hash::make('oldpassword'),
        'unhashed_password' => 'oldpassword',
        'withdrawal_password' => Hash::make('oldtxpass'),
        'unhashed_withdrawal_password' => 'oldtxpass',
        'credit_score' => 50,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('editUser')
        ->set('editName', 'Updated Name')
        ->set('editUsername', 'updateduser')
        ->set('editEmail', 'updated@example.com')
        ->set('editMobileNumber', '+2348000000000')
        ->set('editPassword', '')
        ->set('editTransactionPassword', 'newtxpass')
        ->set('editCreditScore', '75')
        ->call('saveUser')
        ->assertHasNoErrors()
        ->assertSet('editingUserId', null)
        ->assertSet('selectedUserId', null);

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->username)->toBe('updateduser')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->mobile_number)->toBe('+2348000000000')
        ->and((int) $user->credit_score)->toBe(75)
        ->and($user->unhashed_password)->toBe('oldpassword')
        ->and(Hash::check('oldpassword', $user->password))->toBeTrue()
        ->and($user->unhashed_withdrawal_password)->toBe('newtxpass')
        ->and(Hash::check('newtxpass', $user->withdrawal_password))->toBeTrue();
});

it('validates edited details before saving them to the user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'Daniella Dom']);
    User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('editUser')
        ->set('editEmail', 'taken@example.com')
        ->set('editUsername', 'taken')
        ->set('editPassword', '123')
        ->set('editCreditScore', '101')
        ->call('saveUser')
        ->assertHasErrors([
            'editEmail' => 'unique',
            'editUsername' => 'unique',
            'editPassword' => 'min',
            'editCreditScore' => 'max',
        ])
        ->assertSet('editingUserId', $user->id);

    $user->refresh();

    expect($user->email)->not->toBe('taken@example.com');
});

it('sets a required upgrade target for the selected user', function (bool $taskSummary) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'membership_level' => 'Normal',
    ]);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeTarget')
        ->assertSet('selectedUserId', null)
        ->assertSet('upgradeTargetUserId', $user->id)
        ->assertSee('Set Upgrade Target')
        ->assertSee('Daniella Dom')
        ->assertSee('The user will not be able to submit tasks until they upgrade.')
        ->set('upgradeTargetLevel', 'Silver')
        ->call('setUpgradeTarget')
        ->assertHasNoErrors()
        ->assertSet('upgradeTargetUserId', null)
        ->assertDispatched('task-user-updated')
        ->assertDispatched('toast', message: 'Upgrade target set successfully.');

    expect($user->fresh()->required_upgrade_level)->toBe('Silver');

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSee('Upgrade Checkpoint')
        ->assertSee('Silver');
})->with([false, true]);

it('only offers higher upgrade targets on both admin pages', function (bool $taskSummary, string $currentLevel, array $expectedLevels) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => $currentLevel]);
    foreach (['Gold' => 1.00, 'Normal' => 0.50, 'Diamond' => 1.50, 'Silver' => 0.75] as $name => $percentage) {
        MembershipLevel::create(['name' => $name, 'percentage' => $percentage]);
    }

    $component = Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeTarget');

    $document = new DOMDocument;
    @$document->loadHTML($component->html());
    $options = (new DOMXPath($document))->query('//select[@id="upgrade-target-level"]/option[@value!=""]');

    expect(array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($options)))
        ->toBe($expectedLevels);
})->with([false, true])->with([
    'entry level' => ['Normal', ['Silver', 'Gold', 'Diamond']],
    'middle level' => ['Silver', ['Gold', 'Diamond']],
    'highest level' => ['Diamond', []],
]);

it('validates the required upgrade target before saving it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Normal']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeTarget')
        ->call('setUpgradeTarget')
        ->assertHasErrors(['upgradeTargetLevel' => 'required'])
        ->assertSet('upgradeTargetUserId', $user->id)
        ->set('upgradeTargetLevel', 'Nonexistent')
        ->call('setUpgradeTarget')
        ->assertHasErrors(['upgradeTargetLevel' => 'in']);

    expect($user->fresh()->required_upgrade_level)->toBeNull();
});

it('rejects current and lower upgrade targets on both admin pages', function (bool $taskSummary, string $targetLevel) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Silver']);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => 0.50]);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => 0.75]);

    Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeTarget')
        ->set('upgradeTargetLevel', $targetLevel)
        ->call('setUpgradeTarget')
        ->assertHasErrors(['upgradeTargetLevel' => 'in']);

    expect($user->fresh()->required_upgrade_level)->toBeNull();
})->with([false, true])->with(['Normal', 'Silver']);

it('cancels the required upgrade target modal without saving', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Normal']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeTarget')
        ->set('upgradeTargetLevel', 'Silver')
        ->call('closeUpgradeTarget')
        ->assertSet('upgradeTargetUserId', null)
        ->assertSet('upgradeTargetLevel', '');

    expect($user->fresh()->required_upgrade_level)->toBeNull();
});

it('opens the upgrade membership modal with the next level preselected', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'membership_level' => 'Normal',
    ]);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Gold', 'percentage' => '1.00']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSee('wire:click="openUpgradeMembership"', false)
        ->call('openUpgradeMembership')
        ->assertSet('showUpgradeMembership', true)
        ->assertSet('selectedUserId', $user->id)
        ->assertSet('upgradeMembershipLevel', 'Silver')
        ->assertSee('Upgrade Membership')
        ->assertSee('Daniella Dom — Current:')
        ->assertSee('New Membership Level')
        ->assertSee('Normal (current)')
        ->assertSee('Gold');
});

it('upgrades the selected user membership level and clears a satisfied required upgrade', function (bool $taskSummary) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Silver',
    ]);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Gold', 'percentage' => '1.00']);

    Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeMembership')
        ->set('upgradeMembershipLevel', 'Gold')
        ->call('applyUpgradeMembership')
        ->assertHasNoErrors()
        ->assertSet('showUpgradeMembership', false)
        ->assertSet('upgradeMembershipLevel', '')
        ->assertSet('selectedUserId', $user->id)
        ->assertDispatched('task-user-updated')
        ->assertDispatched('toast', message: 'Membership level updated successfully.');

    $user->refresh();

    expect($user->membership_level)->toBe('Gold')
        ->and($user->required_upgrade_level)->toBeNull();
})->with([false, true]);

it('keeps a required upgrade when the new membership level does not reach the target', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Diamond',
    ]);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => '1.50']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeMembership')
        ->set('upgradeMembershipLevel', 'Silver')
        ->call('applyUpgradeMembership')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->membership_level)->toBe('Silver')
        ->and($user->required_upgrade_level)->toBe('Diamond')
        ->and($user->requiresUpgrade())->toBeTrue();
});

it('validates the new membership level before upgrading', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Normal']);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeMembership')
        ->set('upgradeMembershipLevel', '')
        ->call('applyUpgradeMembership')
        ->assertHasErrors(['upgradeMembershipLevel' => 'required'])
        ->assertSet('showUpgradeMembership', true)
        ->set('upgradeMembershipLevel', 'Nonexistent')
        ->call('applyUpgradeMembership')
        ->assertHasErrors(['upgradeMembershipLevel' => 'exists'])
        ->assertSet('showUpgradeMembership', true);

    expect($user->fresh()->membership_level)->toBe('Normal');
});

it('cancels the upgrade membership modal without changing the level', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Normal']);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openUpgradeMembership')
        ->set('upgradeMembershipLevel', 'Silver')
        ->call('closeUpgradeMembership')
        ->assertSet('showUpgradeMembership', false)
        ->assertSet('upgradeMembershipLevel', '')
        ->assertSet('selectedUserId', $user->id);

    expect($user->fresh()->membership_level)->toBe('Normal');
});

it('opens the downgrade membership modal with only lower levels offered', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'membership_level' => 'Gold',
    ]);
    foreach (['Normal' => 0.50, 'Silver' => 0.75, 'Gold' => 1.00, 'Diamond' => 1.50] as $name => $percentage) {
        MembershipLevel::create(['name' => $name, 'percentage' => $percentage]);
    }

    $component = Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSee('wire:click="openDowngradeMembership"', false)
        ->call('openDowngradeMembership')
        ->assertSet('showDowngradeMembership', true)
        ->assertSet('selectedUserId', $user->id)
        ->assertSet('downgradeMembershipLevel', 'Silver')
        ->assertSee('Downgrade Membership')
        ->assertSee('Daniella Dom — Current:')
        ->assertSee('New Membership Level');

    $document = new DOMDocument;
    @$document->loadHTML($component->html());
    $options = (new DOMXPath($document))->query('//select[@id="downgrade-membership-level"]/option');

    expect(array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($options)))
        ->toBe(['Silver', 'Normal']);
});

it('downgrades the selected user membership level', function (bool $taskSummary) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => 'Gold',
        'required_upgrade_level' => 'Diamond',
    ]);
    foreach (['Normal' => 0.50, 'Silver' => 0.75, 'Gold' => 1.00, 'Diamond' => 1.50] as $name => $percentage) {
        MembershipLevel::create(['name' => $name, 'percentage' => $percentage]);
    }

    Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->call('openDowngradeMembership')
        ->set('downgradeMembershipLevel', 'Silver')
        ->call('applyDowngradeMembership')
        ->assertHasNoErrors()
        ->assertSet('showDowngradeMembership', false)
        ->assertSet('downgradeMembershipLevel', '')
        ->assertSet('selectedUserId', $user->id)
        ->assertDispatched('task-user-updated')
        ->assertDispatched('toast', message: 'Membership level updated successfully.');

    $user->refresh();

    expect($user->membership_level)->toBe('Silver')
        ->and($user->required_upgrade_level)->toBe('Diamond');
})->with([false, true]);

it('offers no downgrade levels for a user on the entry level', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Normal']);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openDowngradeMembership')
        ->assertSet('showDowngradeMembership', true)
        ->assertSet('downgradeMembershipLevel', '')
        ->assertSee('already on the lowest membership level')
        ->assertDontSee('id="downgrade-membership-level"', false);
});

it('rejects current, higher, and unknown downgrade levels', function (string $targetLevel) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Silver']);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Gold', 'percentage' => '1.00']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openDowngradeMembership')
        ->set('downgradeMembershipLevel', '')
        ->call('applyDowngradeMembership')
        ->assertHasErrors(['downgradeMembershipLevel' => 'required'])
        ->assertSet('showDowngradeMembership', true)
        ->set('downgradeMembershipLevel', $targetLevel)
        ->call('applyDowngradeMembership')
        ->assertHasErrors(['downgradeMembershipLevel' => 'in'])
        ->assertSet('showDowngradeMembership', true);

    expect($user->fresh()->membership_level)->toBe('Silver');
})->with(['Silver', 'Gold', 'Nonexistent']);

it('cancels the downgrade membership modal without changing the level', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['membership_level' => 'Gold']);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Gold', 'percentage' => '1.00']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openDowngradeMembership')
        ->set('downgradeMembershipLevel', 'Normal')
        ->call('closeDowngradeMembership')
        ->assertSet('showDowngradeMembership', false)
        ->assertSet('downgradeMembershipLevel', '')
        ->assertSet('selectedUserId', $user->id);

    expect($user->fresh()->membership_level)->toBe('Gold');
});

it('clears the selected user submission record and on-hold balance', function (bool $taskSummary) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'tasks_completed' => 12,
        'lien_amount' => '5000',
        'lien_status' => 'on_hold',
    ]);
    $otherUser = User::factory()->create(['tasks_completed' => 4]);

    foreach ([$user, $user, $otherUser] as $taskOwner) {
        CompletedTask::create([
            'user_id' => $taskOwner->id,
            'title' => 'Test Product',
            'cost' => 100,
            'task_image_path' => 'tasks/test.jpg',
            'rating' => '',
            'rating_id' => '',
            'status' => 'approved',
        ]);
    }

    Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->assertSee('wire:confirm="CLEAR RECORD:', false)
        ->call('clearRecord')
        ->assertSet('selectedUserId', null)
        ->assertDispatched('task-user-updated')
        ->assertDispatched('toast', message: 'Record cleared successfully.');

    $user->refresh();

    expect($user->tasks_completed)->toBe(0)
        ->and($user->lien_amount)->toBeNull()
        ->and($user->lien_status)->toBe('off_hold')
        ->and($user->tasks()->count())->toBe(0)
        ->and($otherUser->fresh()->tasks_completed)->toBe(4)
        ->and($otherUser->tasks()->count())->toBe(1);
})->with([false, true]);

it('opens the manage funds modal with the selected user balances', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'balance' => 1000,
        'daily_commission' => 0,
        'lien_status' => 'off_hold',
        'lien_amount' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSee('wire:click="openFunds"', false)
        ->call('openFunds')
        ->assertSet('selectedUserId', null)
        ->assertSet('manageFundsUserId', $user->id)
        ->assertSet('fundsAction', 'add_funds')
        ->assertSet('fundsAmount', '')
        ->assertSee('Manage Funds')
        ->assertSee('Daniella Dom — Balance: $10.00 · On Hold: $0.00 · Today: $0.00')
        ->call('closeFunds')
        ->assertSet('manageFundsUserId', null)
        ->assertDontSee('Manage Funds');
});

it('requires a valid amount before applying a funds action', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['balance' => 1000]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->call('applyFunds')
        ->assertHasErrors(['fundsAmount' => 'required'])
        ->set('fundsAmount', '0')
        ->call('applyFunds')
        ->assertHasErrors(['fundsAmount' => 'min'])
        ->set('fundsAmount', 'not-a-number')
        ->call('applyFunds')
        ->assertHasErrors(['fundsAmount' => 'numeric'])
        ->assertSet('manageFundsUserId', $user->id);

    expect($user->fresh()->balance)->toBe(1000);
});

it('adds funds to the selected user and marks the first deposit rule', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'balance' => 1000,
        'has_made_first_deposit' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'add_funds')
        ->set('fundsAmount', '100')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertSet('manageFundsUserId', null)
        ->assertDispatched('toast', message: 'Funds added successfully.');

    $user->refresh();

    expect($user->balance)->toBe(11000)
        ->and($user->has_made_first_deposit)->toBeTrue();
});

it('deducts funds from the selected user and refuses to deduct more than the balance', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['balance' => 1000]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_funds')
        ->set('fundsAmount', '25')
        ->call('applyFunds')
        ->assertHasErrors(['fundsAmount'])
        ->assertSet('manageFundsUserId', $user->id);

    expect($user->fresh()->balance)->toBe(1000);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_funds')
        ->set('fundsAmount', '4.50')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertSet('manageFundsUserId', null)
        ->assertDispatched('toast', message: 'Funds deducted successfully.');

    expect($user->fresh()->balance)->toBe(550);
});

it("adds and deducts today's earnings for the selected user", function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['daily_commission' => 0]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'add_todays_earnings')
        ->set('fundsAmount', '12.50')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: "Today's earnings added successfully.");

    expect($user->fresh()->daily_commission)->toBe(1250);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_todays_earnings')
        ->set('fundsAmount', '20')
        ->call('applyFunds')
        ->assertHasErrors(['fundsAmount'])
        ->assertSet('manageFundsUserId', $user->id);

    expect($user->fresh()->daily_commission)->toBe(1250);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_todays_earnings')
        ->set('fundsAmount', '5')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertSet('manageFundsUserId', null)
        ->assertDispatched('toast', message: "Today's earnings deducted successfully.");

    expect($user->fresh()->daily_commission)->toBe(750);
});

it('adds and deducts the on-hold balance for the selected user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'lien_status' => 'off_hold',
        'lien_amount' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'add_on_hold')
        ->set('fundsAmount', '30')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'On-hold balance added successfully.');

    $user->refresh();

    expect($user->lien_status)->toBe('on_hold')
        ->and($user->lien_amount)->toBe('3000');

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_on_hold')
        ->set('fundsAmount', '45')
        ->call('applyFunds')
        ->assertHasErrors(['fundsAmount']);

    expect($user->fresh()->lien_amount)->toBe('3000');

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_on_hold')
        ->set('fundsAmount', '10')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'On-hold balance deducted successfully.');

    expect($user->fresh()->lien_amount)->toBe('2000');

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'deduct_on_hold')
        ->set('fundsAmount', '20')
        ->call('applyFunds')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->lien_amount)->toBeNull()
        ->and($user->lien_status)->toBe('off_hold');
});

it('returns the on-hold balance and auto-submits any pending product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    MembershipLevel::create(['name' => 'Normal', 'percentage' => 0.5]);

    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'tasks_completed' => 0,
        'lien_status' => 'on_hold',
        'lien_amount' => '21000',
    ]);

    $pendingTask = CompletedTask::create([
        'user_id' => $user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->set('fundsAction', 'return_balance')
        ->call('applyFunds')
        ->assertHasNoErrors()
        ->assertSet('manageFundsUserId', null)
        ->assertDispatched('toast', message: 'Balance returned successfully.');

    $user->refresh();

    expect($user->lien_status)->toBe('off_hold')
        ->and($user->lien_amount)->toBeNull()
        ->and($pendingTask->fresh()->status)->toBe('approved')
        ->and($user->tasks_completed)->toBe(1)
        ->and($user->daily_commission)->toBe(50)
        ->and($user->total_commission)->toBe(50)
        ->and($user->balance)->toBe(50050);
});

it('shows the return balance description and reflects the amount in the confirm button', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'balance' => 1000,
        'lien_status' => 'on_hold',
        'lien_amount' => '5000',
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openFunds')
        ->assertSee('Add $0.00')
        ->set('fundsAmount', '12.5')
        ->assertSee('Add $12.50')
        ->set('fundsAction', 'deduct_funds')
        ->assertSee('Deduct $12.50')
        ->set('fundsAction', 'add_todays_earnings')
        ->assertSee('Add $12.50 to Today')
        ->set('fundsAction', 'deduct_todays_earnings')
        ->assertSee('Deduct $12.50 from Today')
        ->set('fundsAction', 'add_on_hold')
        ->assertSee('Add $12.50 to On-Hold')
        ->set('fundsAction', 'deduct_on_hold')
        ->assertSee('Deduct $12.50 from On-Hold')
        ->set('fundsAction', 'return_balance')
        ->assertSee("This will add the on-hold balance of $50.00 back to the user's main balance, auto-submit any pending product, and allow them to continue submitting tasks.", false)
        ->assertSee('Return Balance')
        ->assertDontSee('Amount ($)');
});

it('suspends the selected user account and shows a success toast', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'account_status' => 'active',
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSeeHtml('wire:click="suspendUser"')
        ->assertDontSeeHtml('bg-destructive/10')
        ->call('suspendUser')
        ->assertSet('selectedUserId', $user->id)
        ->assertSee('Suspended')
        ->assertSeeHtml('wire:click="unsuspendUser"')
        ->assertDontSeeHtml('wire:click="suspendUser"')
        ->assertSeeHtml('bg-destructive/10')
        ->assertDispatched('toast', message: 'Daniella Dom suspended');

    expect($user->fresh()->account_status)->toBe('suspended');
});

it('unsuspends the selected user account and restores the suspend button', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'account_status' => 'suspended',
        'is_banned' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->assertSee('Unsuspend')
        ->assertSeeHtml('wire:click="unsuspendUser"')
        ->assertSeeHtml('bg-destructive/10')
        ->call('unsuspendUser')
        ->assertSeeHtml('wire:click="suspendUser"')
        ->assertDontSeeHtml('wire:click="unsuspendUser"')
        ->assertDontSeeHtml('bg-destructive/10')
        ->assertDispatched('toast', message: 'Daniella Dom unsuspended');

    expect($user->fresh()->account_status)->toBe('active');

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk();
});

it('uses the username in the suspension toast when the user has no name', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => null,
        'username' => 'Simplicity',
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('suspendUser')
        ->assertDispatched('toast', message: 'Simplicity suspended');

    expect($user->fresh()->account_status)->toBe('suspended');
});

it('deletes the selected user account with all related records and reports success', function (bool $taskSummary) {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/daniella.jpg', 'image-bytes');

    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'name' => 'Daniella Dom',
        'profile_image_path' => 'avatars/daniella.jpg',
    ]);
    $otherUser = User::factory()->create(['name' => 'Lina']);

    $task = CompletedTask::create([
        'user_id' => $user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    $otherTask = CompletedTask::create([
        'user_id' => $otherUser->id,
        'title' => 'Other Product',
        'cost' => 100,
        'task_image_path' => 'tasks/other.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    $alert = Alert::create([
        'user_id' => $user->id,
        'title' => 'Announcement',
        'body' => 'Body',
        'read_status' => 'unread',
    ]);

    $withdrawal = Withdrawal::create([
        'user_id' => $user->id,
        'amount' => 500,
        'payment_method' => 'BTC',
        'address' => 'bc1qexample',
        'status' => 'pending',
    ]);

    $wallet = Wallet::create([
        'user_id' => $user->id,
        'label' => 'Main',
        'cryptocurrency' => 'BTC',
        'address' => 'bc1qexample',
    ]);

    $deposit = Deposit::create([
        'user_id' => $user->id,
        'cryptocurrency' => 'BTC',
        'amount' => 500,
        'proof_of_payment_path' => 'deposit-proof/proof.jpg',
        'status' => 'pending',
    ]);

    DB::table('referrals')->insert([
        'user_id' => $user->id,
        'referral_code' => 'SHNFXQCV47',
        'referred_by' => 'Angel',
        'amount' => 500,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Users::class, ['taskSummary' => $taskSummary])
        ->call('showUserDetails', $user->id)
        ->assertSee('wire:click="deleteUser"', false)
        ->assertSee('wire:confirm="DELETE ACCOUNT:', false)
        ->call('deleteUser')
        ->assertSet('selectedUserId', null)
        ->assertDispatched('task-user-updated')
        ->assertDispatched('toast', message: 'User account deleted successfully.');

    expect(User::query()->find($user->id))->toBeNull()
        ->and(CompletedTask::query()->find($task->id))->toBeNull()
        ->and(Alert::query()->find($alert->id))->toBeNull()
        ->and(Withdrawal::query()->find($withdrawal->id))->toBeNull()
        ->and(Wallet::query()->find($wallet->id))->toBeNull()
        ->and(Deposit::query()->find($deposit->id))->toBeNull()
        ->and(DB::table('referrals')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Storage::disk('public')->exists('avatars/daniella.jpg'))->toBeFalse()
        ->and(User::query()->find($otherUser->id))->not->toBeNull()
        ->and(CompletedTask::query()->find($otherTask->id))->not->toBeNull();
})->with([false, true]);

it('refuses to delete a user when no account is selected', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'Daniella Dom']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('deleteUser')
        ->assertSet('selectedUserId', null)
        ->assertNotDispatched('toast');

    expect(User::query()->find($user->id))->not->toBeNull();
});

it('refuses to delete an administrator account', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $targetAdmin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $targetAdmin->id)
        ->assertSet('selectedUserId', null)
        ->call('deleteUser')
        ->assertNotDispatched('toast');

    expect(User::query()->find($targetAdmin->id))->not->toBeNull();
});

it('redirects the user detail order action to the assign orders page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openAssignOrders')
        ->assertRedirect(route('admin.dashboard.assign-orders', ['user' => $user->id]));
});

it('shows merge tasks and negative balance in the task account summary', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_admin' => false,
        'membership_level' => 'Gold',
        'balance' => 110321,
        'negative_balance' => 5000,
        'tasks_completed' => 5,
    ]);

    $user->tasks()->create([
        'title' => 'Queued merge', 'cost' => '80.00', 'task_image_path' => '',
        'status' => 'queued', 'rating' => '0', 'rating_id' => 'merge-summary',
        'is_merge' => true, 'merge_items' => [['id' => 'one', 'name' => 'One', 'price' => 80]], 'merge_position' => 1,
    ]);

    Livewire::actingAs($admin)->test(Users::class, ['taskSummary' => true])
        ->dispatch('show-task-user', userId: $user->id)
        ->assertSee('Merge Tasks')
        ->assertSee('Negative balance')
        ->assertSee('-$50.00')
        ->assertSee('5 / 30')
        ->assertSee('Assign Orders')
        ->assertDontSee('Premium Tasks');
});

it('updates a user negative balance from the admin adjust dialog', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'negative_balance' => 5000]);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openAdjustNegative')
        ->assertSet('negativeBalanceAmount', '50.00')
        ->set('negativeBalanceAmount', '12.34')
        ->call('saveNegativeBalance')
        ->assertHasNoErrors()
        ->assertSet('showAdjustNegative', false)
        ->assertDispatched('toast', message: 'Negative balance updated.');

    expect((int) $user->fresh()->negative_balance)->toBe(1234);
});

it('rejects negative balance values that are not non-negative amounts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'negative_balance' => 5000]);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openAdjustNegative')
        ->set('negativeBalanceAmount', '-1')
        ->call('saveNegativeBalance')
        ->assertHasErrors('negativeBalanceAmount')
        ->assertSet('showAdjustNegative', true);

    expect((int) $user->fresh()->negative_balance)->toBe(5000);
});

it('credits a golden egg to a user from the admin dialog', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openGoldenEgg')
        ->set('goldenEggAmount', '25.50')
        ->call('creditGoldenEgg')
        ->assertHasNoErrors()
        ->assertSet('showGoldenEgg', false)
        ->assertDispatched('toast');

    $egg = GoldenEgg::query()->where('user_id', $user->id)->sole();

    expect($egg->amount)->toBe(2550)
        ->and($egg->status)->toBe('pending')
        ->and($egg->created_by)->toBe($admin->id)
        ->and((int) $user->fresh()->balance)->toBe(0);
});

it('validates the golden egg amount', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('openGoldenEgg')
        ->set('goldenEggAmount', '0')
        ->call('creditGoldenEgg')
        ->assertHasErrors('goldenEggAmount');

    expect(GoldenEgg::query()->count())->toBe(0);
});

it('forbids non admins from adjusting negative balances or sending golden eggs', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($user)->test(Users::class)
        ->call('openAdjustNegative')->assertForbidden();
    Livewire::actingAs($user)->test(Users::class)
        ->call('saveNegativeBalance')->assertForbidden();
    Livewire::actingAs($user)->test(Users::class)
        ->call('openGoldenEgg')->assertForbidden();
    Livewire::actingAs($user)->test(Users::class)
        ->call('creditGoldenEgg')->assertForbidden();
});
