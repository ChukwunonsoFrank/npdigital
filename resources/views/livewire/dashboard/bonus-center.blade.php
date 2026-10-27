<div class="mx-auto max-w-3xl space-y-5" x-on:toast.self="window.showToast($event.detail.message)">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.start') }}" wire:navigate aria-label="Back to start"
            class="inline-flex h-10 w-10 items-center justify-center rounded-md transition-colors hover:bg-muted">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <div>
            <h1 class="font-display text-2xl font-bold">Bonus Center</h1>
            <p class="text-sm text-muted-foreground">Show up, complete your sets, and collect daily rewards.</p>
        </div>
    </div>

    <section class="relative overflow-hidden rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-500/15 via-amber-500/5 to-card p-5 shadow-sm">
        <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-amber-400/20 blur-2xl"></div>
        <div class="relative flex items-start justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-amber-600 dark:text-amber-300">
                    <x-dashboard.icon name="sparkles" class="h-3 w-3" /> {{ $cycleDays }}-day cycle
                </span>
                <h2 class="mt-3 font-display text-xl font-bold text-foreground">Complete {{ $setsRequired }} sets. Sign in. Earn.</h2>
                <p class="mt-1 max-w-md text-sm text-muted-foreground">Your check-in unlocks after {{ $setsRequired }} completed sets in a day. Keep the streak alive for bigger rewards.</p>
            </div>
            <div class="hidden h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg shadow-amber-500/30 sm:flex">
                <x-dashboard.icon name="gift" class="h-8 w-8" />
            </div>
        </div>
        <div class="relative mt-4 flex flex-wrap items-center gap-2 text-[11px] font-semibold">
            <span class="rounded-full bg-background/70 px-2.5 py-1 text-foreground">{{ ucfirst($level) }} member</span>
            <span class="rounded-full bg-background/70 px-2.5 py-1 text-foreground">Day {{ $claimedToday ? $nextDay : ($unlocked ? $nextDay : 1) }} reward: ${{ number_format((float) ($rewards[max(0, ($claimedToday ? $nextDay : $nextDay) - 1)] ?? 0), 2) }}</span>
        </div>
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h3 class="font-display text-sm font-bold text-foreground">Your reward cycle</h3>
            <span class="text-[11px] text-muted-foreground">{{ count($claimedDays) }}/{{ $cycleDays }} claimed</span>
        </div>

        <div class="mt-4 grid grid-cols-5 gap-1.5 sm:gap-2">
            @for ($day = 1; $day <= $cycleDays; $day++)
                @php
                    $dayReward = (float) ($rewards[$day - 1] ?? ($rewards === [] ? 0 : $rewards[count($rewards) - 1]));
                    $isClaimed = in_array($day, $claimedDays, true);
                    $isToday = ! $claimedToday && $unlocked && $day === $nextDay;
                    $isFinal = $day === $cycleDays;
                @endphp
                <div @class([
                    'relative flex flex-col items-center rounded-xl border px-1.5 py-3 text-center transition-colors',
                    'border-amber-500/50 bg-amber-500/10' => $isToday,
                    'border-emerald-500/30 bg-emerald-500/5' => $isClaimed,
                    'border-border bg-muted/20' => ! $isToday && ! $isClaimed,
                ])>
                    <span @class([
                        'flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold',
                        'bg-amber-500 text-white' => $isToday,
                        'bg-emerald-500 text-white' => $isClaimed,
                        'bg-muted text-muted-foreground' => ! $isToday && ! $isClaimed,
                    ])>
                        @if ($isClaimed)
                            <x-dashboard.icon name="check" class="h-3.5 w-3.5" />
                        @else
                            {{ $day }}
                        @endif
                    </span>
                    <span class="mt-2 text-[10px] text-muted-foreground">Day {{ $day }}</span>
                    <span class="mt-0.5 text-xs font-bold text-foreground">${{ number_format($dayReward, 0) }}</span>
                    @if ($isFinal)
                        <span class="mt-1 rounded-full bg-primary/10 px-1.5 py-0.5 text-[8px] font-bold uppercase text-primary">Level</span>
                    @endif
                </div>
            @endfor
        </div>

        <p class="mt-3 text-[11px] text-muted-foreground">Day {{ $cycleDays }} rewards are boosted by your VIP level. The cycle restarts after {{ $cycleDays }} days.</p>
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        @if ($claimedToday)
            <div class="flex flex-col items-center py-3 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600">
                    <x-dashboard.icon name="circle-check" class="h-6 w-6" />
                </span>
                <h3 class="mt-3 font-display text-lg font-bold text-foreground">Checked in for Day {{ $nextDay }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">Your reward has been added to your balance. Come back tomorrow to keep your streak going.</p>
                <a href="{{ route('dashboard.start') }}" wire:navigate class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-primary px-5 text-sm font-bold text-primary-foreground hover:bg-primary/90">Back to tasks</a>
            </div>
        @elseif (! $unlocked)
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-display text-sm font-bold text-foreground">Today's check-in</h3>
                <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-muted-foreground">
                    <x-dashboard.icon name="lock" class="h-3 w-3" /> Locked
                </span>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">Complete {{ $setsRequired }} sets today to unlock your Day {{ $nextDay }} reward.</p>
            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="font-semibold text-foreground">{{ $setsCompleted }}/{{ $setsRequired }} sets</span>
                <span class="text-muted-foreground">{{ $setsCompleted >= $setsRequired ? 'Unlocked' : 'Keep going' }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                <div class="h-full rounded-full bg-primary transition-all" style="width: {{ min(100, (int) floor($setsCompleted / $setsRequired * 100)) }}%"></div>
            </div>
            <p class="mt-2 text-[11px] text-muted-foreground">Current set: {{ auth()->user()->tasks_completed ?? 0 }}/{{ auth()->user()->productsPerSet() }} products ({{ $progressInSet }}%).</p>
            <a href="{{ route('dashboard.start') }}" wire:navigate class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary font-display text-sm font-bold text-primary-foreground hover:bg-primary/90">
                <x-dashboard.icon name="send" class="h-4 w-4" /> Continue tasks
            </a>
        @else
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-display text-sm font-bold text-foreground">Today's check-in</h3>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-600">
                    <x-dashboard.icon name="circle-check" class="h-3 w-3" /> Unlocked
                </span>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">You completed {{ $setsRequired }} sets today. Claim your Day {{ $nextDay }} reward now.</p>
            <div class="mt-4 flex items-center justify-between rounded-xl border border-amber-500/30 bg-amber-500/5 px-4 py-3">
                <span class="text-sm font-semibold text-foreground">Day {{ $nextDay }} reward</span>
                <span class="font-display text-xl font-bold text-amber-600 dark:text-amber-300">${{ number_format((float) ($rewards[$nextDay - 1] ?? 0), 2) }}</span>
            </div>
            <button type="button" wire:click="claim" wire:loading.attr="disabled" wire:target="claim"
                class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 font-display text-sm font-bold text-white transition-colors hover:bg-amber-600 disabled:opacity-60">
                <x-dashboard.icon name="gift" class="h-4 w-4" />
                <span wire:loading.remove wire:target="claim">Check in &amp; claim</span>
                <span wire:loading wire:target="claim">Claiming...</span>
            </button>
        @endif
    </section>

    <section class="rounded-2xl border border-border bg-card p-5">
        <h3 class="font-display text-sm font-bold text-foreground">How it works</h3>
        <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
            <li class="flex gap-2"><x-dashboard.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Complete {{ $setsRequired }} sets of {{ auth()->user()->productsPerSet() }} products in a day.</li>
            <li class="flex gap-2"><x-dashboard.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Check in once per day to claim that day's reward.</li>
            <li class="flex gap-2"><x-dashboard.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Rewards grow across the {{ $cycleDays }}-day cycle, then reset to Day 1.</li>
            <li class="flex gap-2"><x-dashboard.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Missing a day restarts your streak at Day 1.</li>
        </ul>
    </section>
</div>
