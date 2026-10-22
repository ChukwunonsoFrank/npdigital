<?php

use App\Livewire\Auth\Register;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\UserRegistered;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertOk()
        ->assertSee('data-recaptcha-widget', false)
        ->assertSee('https://www.google.com/recaptcha/api.js', false);
});

test('new users can register', function () {
    Notification::fake();

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify*' => Http::response(['success' => true]),
    ]);

    $response = Livewire::test(Register::class)
        ->set('name', 'Jane Smith')
        ->set('username', 'testuser')
        ->set('email', 'testuser@example.com')
        ->set('country_code', '+234')
        ->set('mobile_number', '1234567890')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('withdrawal_password', '123456')
        ->set('withdrawal_password_confirmation', '123456')
        ->set('termsAndPrivacyPolicyAccepted', true)
        ->set('gRecaptchaResponse', 'fake-token')
        ->call('register');

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(User::query()->where('username', 'testuser')->firstOrFail())
        ->is_training->toBeFalse()
        ->membership_level->toBe('Normal')
        ->name->toBe('Jane Smith')
        ->mobile_number->toBe('+2341234567890');

    Notification::assertSentOnDemand(
        UserRegistered::class,
        function (UserRegistered $notification, array $channels, object $notifiable) {
            return $notifiable->routes['mail'] === 'customersupport.npdigital@gmail.com'
                && $notification->emailAddress === 'testuser';
        },
    );
});

test('users can not register when recaptcha verification fails', function () {
    config(['services.recaptcha.enabled' => true]);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
    ]);

    Livewire::test(Register::class)
        ->set('gRecaptchaResponse', 'invalid-token')
        ->call('register')
        ->assertSet('gRecaptchaResponse', null)
        ->assertDispatched('recaptcha-reset')
        ->assertDispatched('signup-error', message: 'Please confirm you are not a robot.');

    expect(User::query()->count())->toBe(0);
});

test('new users can register without recaptcha when recaptcha is disabled', function () {
    Notification::fake();
    config(['services.recaptcha.enabled' => false]);

    Livewire::test(Register::class)
        ->set('name', 'Alex Taylor')
        ->set('username', 'norecaptchauser')
        ->set('email', 'norecaptchauser@example.com')
        ->set('country_code', '+234')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('withdrawal_password', '123456')
        ->set('withdrawal_password_confirmation', '123456')
        ->set('termsAndPrivacyPolicyAccepted', true)
        ->call('register')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(User::where('username', 'norecaptchauser')->firstOrFail()->mobile_number)->toBe('');
});

test('platform settings block new and already open registration forms until reenabled', function (array $settings, string $message) {
    $this->withoutExceptionHandling();
    Notification::fake();
    config(['services.recaptcha.enabled' => false]);

    $component = Livewire::test(Register::class)
        ->set('name', 'Waiting User')
        ->set('username', 'waitinguser')
        ->set('email', 'waiting@example.com')
        ->set('country_code', '+234')
        ->set('mobile_number', '1234567890')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('withdrawal_password', '123456')
        ->set('withdrawal_password_confirmation', '123456')
        ->set('termsAndPrivacyPolicyAccepted', true);

    $platformSettings = PlatformSetting::factory()->create($settings);

    expect(fn () => $this->get('/register'))->toThrow(HttpException::class, $message);
    expect(fn () => $component->call('register'))->toThrow(HttpException::class, $message);
    expect(User::query()->count())->toBe(0);
    $this->assertGuest();

    $platformSettings->update(['maintenance_mode' => false, 'allow_registrations' => true]);
    $this->get('/register')->assertOk();
    $component->call('register')->assertRedirect('/dashboard');
    expect(User::query()->where('username', 'waitinguser')->firstOrFail()->balance)->toBe(1000);
})->with([
    'registrations disabled' => [['allow_registrations' => false], 'New registrations are currently disabled.'],
    'maintenance enabled' => [['maintenance_mode' => true], 'The platform is temporarily unavailable for maintenance. Please try again later.'],
]);

test('registration validates the new fields and reports errors as toasts', function (string $field, mixed $value, string $error) {
    config(['services.recaptcha.enabled' => false]);

    Livewire::test(Register::class)
        ->set('name', 'Jane Smith')
        ->set('username', 'janesmith')
        ->set('email', 'jane@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('withdrawal_password', '123456')
        ->set('withdrawal_password_confirmation', '123456')
        ->set('termsAndPrivacyPolicyAccepted', true)
        ->set($field, $value)
        ->call('register')
        ->assertHasErrors([$error])
        ->assertDispatched('signup-error');

    expect(User::query()->count())->toBe(0);
})->with([
    'missing name' => ['name', '', 'name'],
    'transaction passwords differ' => ['withdrawal_password_confirmation', '654321', 'withdrawal_password'],
    'terms not accepted' => ['termsAndPrivacyPolicyAccepted', false, 'termsAndPrivacyPolicyAccepted'],
]);
