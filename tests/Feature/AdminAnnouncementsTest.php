<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('renders content management and filters all four categories with accurate counts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    foreach (['announcement', 'notification', 'review', 'update'] as $type) {
        Announcement::factory()->create(['type' => $type, 'title' => 'Only '.$type]);
    }
    Announcement::factory()->create(['title' => 'Second announcement', 'is_active' => false]);

    $this->actingAs($admin)->get('/admin/announcements')->assertOk()
        ->assertSee('Content Management')
        ->assertSee('href="'.route('admin.announcements').'"', false);

    $page = Volt::actingAs($admin)->test('admin.announcements')
        ->assertSee('Only announcement')->assertSee('Second announcement')
        ->assertDontSee('Only notification')
        ->assertViewHas('counts', fn ($counts) => $counts->all() === ['announcement' => 2, 'notification' => 1, 'review' => 1, 'update' => 1]);

    foreach (['notification', 'review', 'update'] as $type) {
        $page->set('tab', $type)->assertSee('Only '.$type)->assertDontSee('Only announcement');
    }
});

it('persists creation editing activation and deletion without affecting other content', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $untouched = Announcement::factory()->create();

    $page = Volt::actingAs($admin)->test('admin.announcements')
        ->set('tab', 'review')->call('create')->assertSet('type', 'review')
        ->set('title', ' New review ')->set('body', ' Useful feedback ')
        ->call('save')->assertHasNoErrors()->assertSet('showForm', false)->assertSee('New review');

    $item = Announcement::query()->where('title', 'New review')->sole();
    expect($item->body)->toBe('Useful feedback');
    $page->call('edit', $item->id)->assertSet('body', 'Useful feedback')
        ->set('type', 'update')->set('title', 'Release notes')->call('save')
        ->assertHasNoErrors()->assertSet('tab', 'update')->assertSee('Release notes')
        ->assertDontSee('Inactive')
        ->call('toggleActive', $item->id)->assertSee('Inactive');
    expect($item->refresh()->is_active)->toBeFalse();
    $page->call('toggleActive', $item->id)->assertDontSee('Inactive');
    expect($item->refresh()->is_active)->toBeTrue();
    $page->call('delete', $item->id)->assertSee('No updates yet.');
    expect(Announcement::query()->find($item->id))->toBeNull();
    expect($untouched->fresh())->not->toBeNull();
});

it('validates content and resets cancelled forms', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.announcements')->call('create')
        ->set('type', 'invalid')->set('title', ' ')->set('body', ' ')
        ->call('save')->assertHasErrors(['type', 'title', 'body'])
        ->set('type', 'announcement')->set('title', str_repeat('a', 256))->set('body', str_repeat('b', 10001))
        ->call('save')->assertHasErrors(['title' => 'max', 'body' => 'max'])
        ->call('closeForm')->assertHasNoErrors()->assertSet('title', '')->assertSet('showForm', false);
    expect(Announcement::query()->count())->toBe(0);
});

it('restricts the route and livewire actions to administrators', function () {
    $this->get('/admin/announcements')->assertRedirect(route('login'));
    $user = User::factory()->create(['is_admin' => false]);
    $this->withoutExceptionHandling();
    $this->actingAs($user);
    expect(fn () => $this->get('/admin/announcements'))->toThrow(HttpException::class);
    expect(fn () => Volt::actingAs($user)->test('admin.announcements'))->toThrow(HttpException::class);
});

it('saves popup settings and targeted users and clears recipients when switching to all users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $users = User::factory()->count(2)->create(['is_admin' => false]);

    $page = Volt::actingAs($admin)->test('admin.announcements')->call('create')
        ->assertSet('show_as_popup', false)->assertSet('target_audience', 'all')
        ->set('title', 'Targeted announcement')->set('body', 'For selected users only.')
        ->set('show_as_popup', true)->set('target_audience', 'specific')
        ->set('selectedUserIds', $users->modelKeys())->call('save')->assertHasNoErrors();

    $item = Announcement::query()->sole();
    expect($item->show_as_popup)->toBeTrue();
    expect($item->target_audience)->toBe('specific');
    expect($item->recipients->modelKeys())->toEqualCanonicalizing($users->modelKeys());

    $page->call('edit', $item->id)->assertSet('show_as_popup', true)
        ->assertSet('target_audience', 'specific')->assertSet('selectedUserIds', $users->modelKeys())
        ->set('userSearch', 'unmatched')->call('closeForm')->call('create')
        ->assertSet('selectedUserIds', [])->assertSet('userSearch', '')
        ->assertSet('show_as_popup', false)->assertSet('target_audience', 'all')
        ->call('edit', $item->id)->set('target_audience', 'all')->set('show_as_popup', false)
        ->call('save')->assertHasNoErrors();

    expect($item->refresh()->target_audience)->toBe('all');
    expect($item->show_as_popup)->toBeFalse();
    expect($item->recipients()->count())->toBe(0);
});

it('requires valid non-admin recipients for a specific audience', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Volt::actingAs($admin)->test('admin.announcements')->call('create')
        ->set('title', 'Valid title')->set('body', 'Valid message')
        ->set('target_audience', 'invalid')->call('save')->assertHasErrors('target_audience')
        ->set('target_audience', 'specific')->call('save')->assertHasErrors('selectedUserIds')
        ->set('selectedUserIds', [$admin->id])->call('save')->assertHasErrors('selectedUserIds.0')
        ->set('selectedUserIds', [999999])->call('save')->assertHasErrors('selectedUserIds.0');

    $user = User::factory()->create(['is_admin' => false]);
    $page->set('selectedUserIds', [$user->id, $user->id])->call('save')
        ->assertHasErrors('selectedUserIds.0');
    expect(Announcement::query()->count())->toBe(0);
});

it('searches audience users by name or username without losing selections or exposing admins', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Search match']);
    $byName = User::factory()->create(['is_admin' => false, 'name' => 'Search match', 'username' => 'alpha']);
    $byUsername = User::factory()->create(['is_admin' => false, 'name' => 'Other person', 'username' => 'search_match']);
    $unmatched = User::factory()->create(['is_admin' => false, 'name' => 'Unrelated', 'username' => 'unrelated']);

    Volt::actingAs($admin)->test('admin.announcements')->call('create')
        ->set('target_audience', 'specific')->set('selectedUserIds', [$unmatched->id])
        ->set('userSearch', 'Search')
        ->assertViewHas('audienceUsers', fn ($users) => $users->modelKeys() === [$byName->id, $byUsername->id])
        ->assertSet('selectedUserIds', [$unmatched->id])->assertSee('1 user selected')
        ->set('userSearch', 'no-such-user')->assertSee('No users found.')
        ->assertSet('selectedUserIds', [$unmatched->id]);
});
