<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth.layout')]

#[Title('Forgot Password')]

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $linkSent = false;

    public function exception(\Throwable $e): void
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('notify', message: $e->validator->errors()->first());
        }
    }

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($this->only('email'));
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notify', message: 'We could not send the reset link. Please try again later.');

            return;
        }

        if ($status === Password::ResetThrottled) {
            $this->dispatch('notify', message: __($status));

            return;
        }

        $this->linkSent = true;
        $this->dispatch('notify', message: __('A reset link will be sent if the account exists.'));
    }
}
