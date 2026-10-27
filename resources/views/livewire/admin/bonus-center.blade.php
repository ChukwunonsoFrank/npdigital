<div class="space-y-4" x-on:toast.self="window.showToast($event.detail.message)">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" aria-label="Back to overview"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-bold">Bonus Center</h1>
                <p class="text-sm text-muted-foreground">Configure daily check-in rewards and the unlock requirement.</p>
            </div>
        </div>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-full bg-[hsl(190,80%,50%)] px-5 text-sm font-medium text-white transition-colors hover:bg-[hsl(190,80%,45%)] disabled:opacity-50">
            <x-dashboard.icon name="save" class="h-4 w-4" /> Save changes
        </button>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Eligible users today</p>
            <p class="mt-1 font-display text-2xl font-bold">{{ $eligibleToday }}</p>
            <p class="mt-1 text-[11px] text-muted-foreground">Completed {{ $setsRequired }} sets</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Check-ins today</p>
            <p class="mt-1 font-display text-2xl font-bold text-primary">{{ $checkInsToday }}</p>
            <p class="mt-1 text-[11px] text-muted-foreground">{{ $activeCycleDays }}-day reward cycle</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Bonuses paid today</p>
            <p class="mt-1 font-display text-2xl font-bold text-emerald-600">${{ number_format($paidToday / 100, 2) }}</p>
            <p class="mt-1 text-[11px] text-muted-foreground">Credited to user balances</p>
        </div>
    </div>

    <form wire:submit="save" class="rounded-xl border border-border bg-card shadow-sm">
        <div class="border-b border-border p-5">
            <h2 class="font-display text-base font-bold">Unlock rules</h2>
            <p class="mt-0.5 text-sm text-muted-foreground">A user can check in once per day after completing the required number of sets.</p>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <flux:input wire:model="setsRequired" type="number" min="1" max="10" label="Sets required per day" />
                    @error('setsRequired') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>
                <div>
                    <flux:input wire:model.live.debounce.400ms="cycleDays" type="number" min="1" max="10" label="Cycle length (days)" />
                    @error('cycleDays') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                    <p class="mt-1 text-[11px] text-muted-foreground">Changing the cycle resizes the reward columns below.</p>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-display text-base font-bold">Rewards by membership level</h2>
                    <p class="mt-0.5 text-sm text-muted-foreground">Day {{ $cycleDays }} is the level-boosted reward. Enter amounts in USD.</p>
                </div>
                <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">5-day default</span>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-border">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-3 py-3 text-left">Level</th>
                            @for ($day = 1; $day <= $cycleDays; $day++)
                                <th class="px-3 py-3 text-center">Day {{ $day }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($levels as $level)
                            <tr wire:key="bonus-level-{{ $level }}" class="border-t border-border/60">
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <x-dashboard.icon name="crown" class="h-4 w-4" />
                                        </span>
                                        <span class="font-semibold">{{ $level }}</span>
                                    </div>
                                </td>
                                @for ($index = 0; $index < $cycleDays; $index++)
                                    <td class="px-2 py-2">
                                        <flux:input wire:model="rewards.{{ $level }}.{{ $index }}" type="number" min="0" step="0.01" aria-label="{{ $level }} day {{ $index + 1 }} reward" class="min-w-24" />
                                        @error('rewards.'.$level.'.'.$index) <p class="mt-1 text-[10px] text-destructive">{{ $message }}</p> @enderror
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('rewards') <p class="mt-2 text-xs text-destructive">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-border p-5 sm:flex-row sm:justify-end">
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                <x-dashboard.icon name="save" class="h-4 w-4" /> Save settings
            </button>
        </div>
    </form>
</div>
