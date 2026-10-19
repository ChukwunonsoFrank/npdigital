<div>
    <x-auth.card heading="Forgot Password" :back="route('login')">
        <p class="text-center text-sm leading-relaxed text-muted-foreground">Enter your email address and we'll send you a link to reset your password.</p>
        <form wire:submit="sendPasswordResetLink" class="space-y-5">
            <x-auth.field name="email" label="Email" type="email" placeholder="you@example.com" autocomplete="email" />
            <flux:button type="submit" variant="primary" class="auth-submit h-12! w-full rounded-xl! text-base! font-black! shadow-lg" wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Send Reset Link</span>
                <span wire:loading wire:target="sendPasswordResetLink">Sending…</span>
            </flux:button>
        </form>
        @if ($linkSent)
            <div role="status" class="rounded-xl border border-primary/20 bg-primary/5 p-4 text-sm leading-relaxed text-muted-foreground">
                <p class="font-bold text-foreground">Check your inbox</p>
                <p>If an account exists for this email, you'll receive a password reset link. Check your spam folder too.</p>
            </div>
        @endif
        <p class="text-center text-sm font-bold text-muted-foreground">Remember your password? <a href="{{ route('login') }}" class="font-extrabold text-primary hover:underline">Sign In</a></p>
    </x-auth.card>
</div>
