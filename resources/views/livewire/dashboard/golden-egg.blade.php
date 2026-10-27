<div x-on:toast.self="window.showToast($event.detail.message)">
    @if ($pendingEgg && ! $showCelebration)
        <div class="fixed bottom-[calc(68px+env(safe-area-inset-bottom)+1rem)] right-24 z-40 flex items-center gap-2 md:bottom-6 md:right-24" x-data="{ seen: false }">
            <div class="hidden rounded-xl border border-amber-500/30 bg-card px-3 py-2 shadow-lg sm:block">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-amber-600 dark:text-amber-300">Golden Egg</p>
                <p class="text-xs text-muted-foreground">A gift is waiting for you</p>
            </div>
            <button type="button" wire:click="claim" wire:loading.attr="disabled" wire:target="claim"
                aria-label="Crack your golden egg"
                class="group relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-300 via-amber-400 to-amber-600 text-white shadow-xl shadow-amber-500/40 ring-4 ring-amber-300/30 transition-transform hover:scale-105 disabled:opacity-60">
                <span class="absolute inset-0 animate-ping rounded-2xl bg-amber-400/30" aria-hidden="true"></span>
                <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white shadow">1</span>
                <x-dashboard.icon name="egg" class="relative h-8 w-8 drop-shadow" />
                <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-amber-500 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wide text-white shadow">Tap to crack</span>
            </button>
        </div>
    @endif

    @if ($showCelebration)
        <style>
            @keyframes egg-confetti-fall {
                0% { transform: translate3d(0, -20px, 0) rotate(0deg); opacity: 1; }
                100% { transform: translate3d(var(--drift, 20px), 260px, 0) rotate(540deg); opacity: 0; }
            }
            .egg-confetti {
                animation: egg-confetti-fall 1.8s ease-in forwards;
                animation-delay: var(--delay, 0s);
            }
        </style>

        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="golden-egg-celebration">
            <div class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-amber-500/40 bg-card p-6 text-center shadow-2xl">
                @for ($piece = 0; $piece < 14; $piece++)
                    <span class="egg-confetti pointer-events-none absolute left-1/2 top-8 h-2 w-2 rounded-sm"
                        style="background: {{ ['#f59e0b', '#fbbf24', '#10b981', '#3b82f6', '#ec4899'][$piece % 5] }}; --drift: {{ (($piece % 7) - 3) * 22 }}px; --delay: {{ ($piece % 5) * 0.08 }}s;"></span>
                @endfor

                <div class="relative mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 via-amber-400 to-amber-600 shadow-xl shadow-amber-500/40">
                    <x-dashboard.icon name="egg" class="h-12 w-12 text-white drop-shadow" />
                    <span class="absolute -right-1 -top-1 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg">
                        <x-dashboard.icon name="sparkles" class="h-4 w-4" />
                    </span>
                </div>

                <h2 id="golden-egg-celebration" class="mt-4 font-display text-xl font-bold text-foreground">Golden Egg cracked!</h2>
                <p class="mt-1 text-sm text-muted-foreground">A surprise reward from the NP Digital team.</p>

                <div class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-amber-600 dark:text-amber-300">Added to your balance</p>
                    <p class="font-display text-3xl font-bold text-amber-600 dark:text-amber-300">+${{ number_format($claimedAmount / 100, 2) }}</p>
                </div>

                <button type="button" wire:click="dismiss"
                    class="mt-5 inline-flex h-10 w-full items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                    Continue
                </button>
            </div>
        </div>
    @endif
</div>
