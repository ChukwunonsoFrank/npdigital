<div x-data x-init="$wire.set('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone)"
    @login-error="window.showToast($event.detail.message)" @recaptcha-reset="window.grecaptcha?.reset()">
    <x-auth.card heading="Sign In">
        <form wire:submit="login" class="space-y-5">
            <x-auth.field name="username" label="Email or Username" placeholder="Email or Username" autocomplete="username" />
            <x-auth.field name="password" label="Password" icon="lock" type="password" placeholder="Password" autocomplete="current-password" />
            <div class="text-right">
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-primary hover:underline">Forgot Password?</a>
            </div>
            <x-auth.recaptcha />
            <flux:button type="submit" variant="primary" class="auth-submit h-12! w-full rounded-xl! text-base! font-black! shadow-lg" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">Sign In</span>
                <span wire:loading wire:target="login">Signing In…</span>
            </flux:button>
        </form>
        <p class="text-center text-sm font-bold text-muted-foreground">Don't have an account? <a href="{{ route('register') }}" class="font-extrabold text-primary hover:underline">Sign Up</a></p>
    </x-auth.card>
</div>
