<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response
        ->assertStatus(200)
        ->assertSee(asset('assets/np/logo-color.png'))
        ->assertSee('Send Reset Link');
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'user@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertSet('linkSent', true)
        ->assertSee('Check your inbox')
        ->assertDispatched('notify', message: 'A reset link will be sent if the account exists.');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'user@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response
            ->assertStatus(200)
            ->assertSee(asset('assets/np/logo-color.png'))
            ->assertSee('Reset Password');

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'user@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = Livewire::test(ResetPassword::class, ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', 'NewPassword123!')
            ->set('password_confirmation', 'NewPassword123!')
            ->call('resetPassword');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status', __(Password::PasswordReset));

        expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('unknown reset accounts receive the same confirmation', function () {
    Notification::fake();
    Livewire::test(ForgotPassword::class)
        ->set('email', 'unknown@example.com')
        ->call('sendPasswordResetLink')
        ->assertSet('linkSent', true)
        ->assertDispatched('notify', message: 'A reset link will be sent if the account exists.');
    Notification::assertNothingSent();
});

test('reset requests report validation errors and throttled requests through toasts', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $form = Livewire::test(ForgotPassword::class)
        ->set('email', 'invalid-email')
        ->call('sendPasswordResetLink')
        ->assertHasErrors('email')
        ->assertDispatched('notify');

    $form->set('email', $user->email)->call('sendPasswordResetLink')->assertHasNoErrors();
    $form->call('sendPasswordResetLink')->assertDispatched('notify', message: __(Password::ResetThrottled));
});

test('invalid tokens and mismatched passwords cannot reset a password and produce toasts', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $form = Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
        ->set('email', $user->email)
        ->set('password', 'NewPassword123!')
        ->set('password_confirmation', 'DifferentPassword123!')
        ->call('resetPassword')
        ->assertHasErrors('password')
        ->assertDispatched('notify');

    $form->set('password_confirmation', 'NewPassword123!')
        ->call('resetPassword')
        ->assertHasErrors('email')
        ->assertDispatched('notify', message: __(Password::InvalidToken));

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
