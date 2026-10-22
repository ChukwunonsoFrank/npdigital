<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use App\Notifications\UserLoggedIn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    config(['services.recaptcha.enabled' => true]);

    $response = $this->get('/login');

    $response
        ->assertStatus(200)
        ->assertDontSee('data-recaptcha-widget', false)
        ->assertDontSee('https://www.google.com/recaptcha/api.js', false)
        ->assertSee(route('password.request', absolute: false))
        ->assertDontSee('href="'.route('password.request').'" class="text-muted"', false);
});

test('users can authenticate using the login screen', function () {
    Notification::fake();
    Http::fake();
    config(['services.recaptcha.enabled' => true]);

    $user = User::factory()->create([
        'timezone' => 'Africa/Lagos',
    ]);

    $response = Livewire::test(Login::class)
        ->set('username', $user->username)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors();

    $this->assertAuthenticated();
    Http::assertNothingSent();
    expect($user->fresh()->timezone)->toBe('Africa/Lagos');

    Notification::assertSentOnDemand(
        UserLoggedIn::class,
        function (UserLoggedIn $notification, array $channels, object $notifiable) use ($user) {
            return $notifiable->routes['mail'] === 'customersupport.npdigital@gmail.com'
                && $notification->username === $user->username;
        },
    );
});

test('admins are redirected to the admin dashboard after login', function () {
    Notification::fake();
    config(['services.recaptcha.enabled' => false]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::test(Login::class)
        ->set('username', $admin->username)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/dashboard');

    $this->assertAuthenticatedAs($admin);
});

test('login updates the users timezone when the browser supplies a valid timezone', function () {
    Notification::fake();

    $user = User::factory()->create([
        'timezone' => 'UTC',
    ]);

    Livewire::test(Login::class)
        ->set('username', $user->username)
        ->set('password', 'password')
        ->set('timezone', 'Africa/Lagos')
        ->call('login')
        ->assertHasNoErrors();

    expect($user->fresh()->timezone)->toBe('Africa/Lagos');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = Livewire::test(Login::class)
        ->set('username', $user->username)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('username');

    $this->assertGuest();
});

test('users can authenticate without completing recaptcha', function () {
    Notification::fake();
    config(['services.recaptcha.enabled' => true]);

    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('username', $user->username)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

test('login remains rate limited without recaptcha', function () {
    $this->freezeTime();
    config(['services.recaptcha.enabled' => true]);

    $user = User::factory()->create();

    $form = Livewire::test(Login::class)
        ->set('username', $user->username)
        ->set('password', 'wrong-password');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $form->call('login')->assertHasErrors('username');
    }

    $form->set('password', 'password')
        ->call('login')
        ->assertHasErrors('username')
        ->assertDispatched('login-error', message: __('auth.throttle', ['seconds' => 60, 'minutes' => 1]));

    $this->assertGuest();
});

test('users can authenticate without recaptcha when recaptcha is disabled', function () {
    Notification::fake();
    config(['services.recaptcha.enabled' => false]);

    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('username', $user->username)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticated();
});

test('users can sign in with their email and receive a toast for invalid credentials', function () {
    Notification::fake();
    config(['services.recaptcha.enabled' => false]);
    $user = User::factory()->create(['username' => 'janesmith', 'email' => 'jane@example.com']);

    $form = Livewire::test(Login::class)
        ->set('username', 'jane@example.com')
        ->set('password', 'incorrect-password')
        ->call('login')
        ->assertHasErrors('username')
        ->assertDispatched('login-error', message: __('auth.failed'));

    $this->assertGuest();

    $form->set('password', 'password')->call('login')->assertHasNoErrors()->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');

    $this->assertGuest();
});
