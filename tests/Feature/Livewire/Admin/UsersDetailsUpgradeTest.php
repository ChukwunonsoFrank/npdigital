<?php

use App\Livewire\Admin\UsersDetails;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('clears the required upgrade once the admin upgrades the user to the target level', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Silver',
    ]);

    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('selectedMembershipLevel', 'Silver')
        ->call('upgradeMembership')
        ->assertDispatched('notify', message: 'Membership level updated successfully', type: 'success');

    $user->refresh();

    expect($user->membership_level)->toBe('Silver')
        ->and($user->required_upgrade_level)->toBeNull();
});

it('clears the required upgrade when the user is upgraded beyond the target level', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Silver',
    ]);

    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => '1.50']);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('selectedMembershipLevel', 'Diamond')
        ->call('upgradeMembership');

    $user->refresh();

    expect($user->membership_level)->toBe('Diamond')
        ->and($user->required_upgrade_level)->toBeNull()
        ->and($user->requiresUpgrade())->toBeFalse();
});

it('keeps the required upgrade when the new level does not reach the target', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Diamond',
    ]);

    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.50']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => '1.50']);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('selectedMembershipLevel', 'Silver')
        ->call('upgradeMembership');

    $user->refresh();

    expect($user->membership_level)->toBe('Silver')
        ->and($user->required_upgrade_level)->toBe('Diamond')
        ->and($user->requiresUpgrade())->toBeTrue();
});
