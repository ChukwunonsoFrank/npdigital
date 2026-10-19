<div>
    <x-auth.card heading="Reset Password" :back="route('login')">
        <p class="text-center text-sm leading-relaxed text-muted-foreground">Choose a new password to securely access your account.</p>
        <form wire:submit="resetPassword" class="space-y-5">
            <x-auth.field name="email" label="Email" type="email" placeholder="you@example.com" autocomplete="email" />
            <x-auth.field name="password" label="New Password" icon="lock" type="password" placeholder="New password" autocomplete="new-password" />
            <x-auth.field name="password_confirmation" label="Confirm Password" icon="lock" type="password" placeholder="Confirm password" autocomplete="new-password" />
            @error('email')
                <a href="{{ route('password.request') }}" class="block text-sm font-semibold text-primary hover:underline">Request a new reset link</a>
            @enderror
            <flux:button type="submit" variant="primary" class="auth-submit h-12! w-full rounded-xl! text-base! font-black! shadow-lg" wire:loading.attr="disabled" wire:target="resetPassword">
                <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
                <span wire:loading wire:target="resetPassword">Resetting…</span>
            </flux:button>
        </form>
        <p class="text-center text-sm font-bold text-muted-foreground">Back to <a href="{{ route('login') }}" class="font-extrabold text-primary hover:underline">Sign In</a></p>
    </x-auth.card>
</div>
