<?php

use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets an admin preview a user dashboard in read-only mode', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_banned' => false]);
    $user = User::factory()->create(['name' => 'Daniella Dom', 'is_admin' => false]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('viewAsUser')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    expect(session('view_as_admin_id'))->toBe($admin->id)
        ->and(session('view_as_user_id'))->toBe($user->id);

    $this->withSession([
        'view_as_admin_id' => $admin->id,
        'view_as_user_id' => $user->id,
    ])->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Viewing as user — read-only admin preview')
        ->assertSee('Back to Admin')
        ->assertSee('Daniella Dom');
});

it('shows the preview banner across the user dashboard', function (string $route) {
    $admin = User::factory()->create(['is_admin' => true, 'is_banned' => false]);
    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false]);

    $this->actingAs($user)
        ->withSession([
            'view_as_admin_id' => $admin->id,
            'view_as_user_id' => $user->id,
        ])
        ->get(route($route))
        ->assertOk()
        ->assertSee('Viewing as user — read-only admin preview')
        ->assertSee('Back to Admin');
})->with([
    'home' => ['dashboard'],
    'wallet' => ['dashboard.wallet'],
    'account' => ['dashboard.account'],
]);

it('does not show the preview banner for a normal user session', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_banned' => false]))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Viewing as user — read-only admin preview');
});

it('blocks actions on the user dashboard while previewing', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_banned' => false]);
    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false]);

    $this->actingAs($user)
        ->withSession([
            'view_as_admin_id' => $admin->id,
            'view_as_user_id' => $user->id,
        ])
        ->post('/livewire/update')
        ->assertForbidden();
});

it('allows dashboard actions when no preview is active', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_banned' => false]))
        ->post('/livewire/update')
        ->assertOk();
});

it('returns the admin to user management after exiting the preview', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_banned' => false]);
    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false]);

    $this->actingAs($user)
        ->withSession([
            'view_as_admin_id' => $admin->id,
            'view_as_user_id' => $user->id,
        ])
        ->post(route('view-as.exit'))
        ->assertRedirect(route('admin.dashboard.users'))
        ->assertSessionMissing('view_as_admin_id')
        ->assertSessionMissing('view_as_user_id');

    $this->assertAuthenticatedAs($admin);
});

it('rejects exiting a preview without a preview session', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_banned' => false]))
        ->post(route('view-as.exit'))
        ->assertForbidden();
});

it('only lets admins start a preview and refuses banned accounts', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false]);
    $otherUser = User::factory()->create(['name' => 'Other User']);

    Livewire::actingAs($user)
        ->test(Users::class)
        ->call('showUserDetails', $otherUser->id)
        ->call('viewAsUser')
        ->assertNoRedirect();

    $this->assertAuthenticatedAs($user);
    expect(session('view_as_admin_id'))->toBeNull();

    $bannedUser = User::factory()->create(['is_banned' => true]);
    $admin = User::factory()->create(['is_admin' => true, 'is_banned' => false]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $bannedUser->id)
        ->call('viewAsUser')
        ->assertNoRedirect()
        ->assertDispatched('toast', message: 'Banned accounts cannot be viewed in preview mode.');

    $this->assertAuthenticatedAs($admin);
    expect(session('view_as_admin_id'))->toBeNull();
});

it('refuses to preview a suspended account', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_banned' => false]);
    $user = User::factory()->create(['account_status' => 'suspended']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('showUserDetails', $user->id)
        ->call('viewAsUser')
        ->assertNoRedirect()
        ->assertDispatched('toast', message: 'Suspended accounts cannot be viewed in preview mode.');

    $this->assertAuthenticatedAs($admin);
    expect(session('view_as_admin_id'))->toBeNull();
});
