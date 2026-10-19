<div x-data="{ open: null }" class="space-y-4 animate-fade-in sm:space-y-5">
    @error('upgrade')
        <p role="alert" class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{{ $message }}</p>
    @enderror
    <div class="flex items-center gap-3 rounded-xl p-5 text-white shadow-sm sm:gap-4 sm:p-7"
        style="background: linear-gradient(120deg, #2f7fb8 0%, #8ec9e0 100%);">
        <a href="{{ route('dashboard') }}" wire:navigate aria-label="Back to dashboard"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white transition-colors hover:bg-white/15">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <div class="min-w-0">
            <h1 class="font-display text-2xl font-bold sm:text-3xl">Activities</h1>
            <p class="mt-0.5 text-sm text-white/80 sm:text-base">Earnings &amp; Membership Information</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <button type="button" @click="open = open === 1 ? null : 1"
            class="flex w-full items-center gap-4 p-4 text-left transition-colors hover:bg-muted/40 sm:p-5">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                <x-dashboard.icon name="calendar-check" class="h-7 w-7 text-primary" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold uppercase tracking-wide text-foreground sm:text-base">Check In Program</span>
                <span class="mt-0.5 block text-xs text-muted-foreground sm:text-sm">Earn rewards for consecutive check-ins</span>
            </span>
            <x-dashboard.icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted-foreground transition-transform"
                x-bind:class="open === 1 ? 'rotate-180' : ''" />
        </button>
        <div x-show="open === 1" x-cloak x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            class="border-t border-border px-4 pb-5 pt-4 sm:px-5">
            <div class="relative mx-auto w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl"
                style="background: radial-gradient(at 50% 30%, rgb(21, 50, 33), rgb(8, 23, 14) 70%);">
                <div class="absolute inset-0 opacity-30"
                    style="background-image: radial-gradient(1px 1px at 20% 30%, white, transparent), radial-gradient(1px 1px at 40% 70%, white, transparent), radial-gradient(1px 1px at 60% 20%, white, transparent), radial-gradient(1px 1px at 80% 50%, white, transparent), radial-gradient(1px 1px at 10% 80%, white, transparent), radial-gradient(1px 1px at 70% 90%, white, transparent), radial-gradient(1px 1px, white, transparent), radial-gradient(1px 1px at 90% 15%, white, transparent);">
                </div>
                <div class="relative z-10 space-y-5 p-6 pb-8">
                    <div class="space-y-1 text-center">
                        <p class="font-display text-sm font-bold uppercase tracking-[0.2em]"
                            style="color: hsl(145 67% 60%);">NP Digital</p>
                        <h2 class="font-display text-2xl font-bold tracking-tight text-white"
                            style="text-shadow: rgb(39 196 104 / 0.5) 0 0 20px;">Check In Program</h2>
                    </div>

                    <div class="space-y-3">
                        @foreach ($checkInMilestones as $milestone)
                            <div wire:key="check-in-{{ $milestone['day'] }}"
                                class="flex items-center gap-3 rounded-xl border p-3 backdrop-blur-sm"
                                style="border-color: hsl(145 50% 22%); background: hsl(145 40% 10% / 0.7);">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg"
                                    style="background: linear-gradient(135deg, rgb(27, 136, 72), rgb(42, 213, 113));">
                                    <span
                                        class="font-display text-lg font-bold text-white">{{ str_pad((string) $milestone['day'], 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium" style="color: hsl(145 25% 60%);">Check-in</p>
                                    <p class="text-sm font-bold" style="color: hsl(145 15% 82%);">Day {{ $milestone['day'] }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-display text-xl font-bold"
                                        style="color: hsl(45 90% 60%); text-shadow: rgb(242 185 13 / 0.3) 0 0 12px;">
                                        ${{ number_format((float) $milestone['reward'], (float) $milestone['reward'] == (int) $milestone['reward'] ? 0 : 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-2 pt-2">
                        <p class="font-display text-center text-xs font-bold uppercase tracking-wider"
                            style="color: hsl(145 30% 70%);">Terms &amp; Conditions</p>
                        <div class="space-y-1.5">
                            @foreach ($checkInTerms as $term)
                                <p class="flex items-start gap-1.5 text-[10px] leading-relaxed"
                                    style="color: hsl(145 12% 60%);">
                                    <span class="mt-px" style="color: hsl(145 60% 50%);">&bull;</span>
                                    <span>{{ $term }}</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <button type="button" @click="open = open === 2 ? null : 2"
            class="flex w-full items-center gap-4 p-4 text-left transition-colors hover:bg-muted/40 sm:p-5">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                <x-dashboard.icon name="trending-up" class="h-7 w-7 text-primary" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold uppercase tracking-wide text-foreground sm:text-base">Membership Level Chart</span>
                <span class="mt-0.5 block text-xs text-muted-foreground sm:text-sm">Benefits and requirements for each level</span>
            </span>
            <x-dashboard.icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted-foreground transition-transform"
                x-bind:class="open === 2 ? 'rotate-180' : ''" />
        </button>
        <div x-show="open === 2" x-cloak x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            class="border-t border-border px-4 pb-5 pt-4 sm:px-5">
            <div class="space-y-6">
                <div class="relative mx-auto w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl"
                    style="background: radial-gradient(at 50% 30%, rgb(21, 50, 33), rgb(8, 23, 14) 70%);">
                    <div class="absolute inset-0 opacity-30"
                        style="background-image: radial-gradient(1px 1px at 15% 25%, white, transparent), radial-gradient(1px 1px at 45% 65%, white, transparent), radial-gradient(1px 1px at 75% 15%, white, transparent), radial-gradient(1px 1px at 85% 55%, white, transparent), radial-gradient(1px 1px at 25% 85%, white, transparent), radial-gradient(1px 1px at 55% 45%, white, transparent);">
                    </div>
                    <div class="relative z-10 space-y-5 p-6 pb-8">
                        <div class="space-y-1 text-center">
                            <p class="font-display text-sm font-bold uppercase tracking-[0.2em] text-[hsl(145,67%,60%)]">
                                NP Digital</p>
                            <h2 class="font-display text-xl font-bold uppercase tracking-tight text-white"
                                style="text-shadow: rgba(39, 196, 104, 0.5) 0 0 20px;">Membership Level Chart</h2>
                            <p class="text-xs text-[hsl(145,25%,60%)]">Benefits and requirements for each level</p>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-[hsl(145,50%,22%)] backdrop-blur-sm"
                            style="background: hsl(145 40% 10% / 0.6);">
                            <div class="grid grid-cols-4 gap-1 border-b border-[hsl(145,40%,20%)] px-4 py-3">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[hsl(145,30%,70%)]">Level</span>
                                <span class="text-center text-[10px] font-bold uppercase tracking-wider text-[hsl(145,30%,70%)]">Deposit</span>
                                <span class="text-center text-[10px] font-bold uppercase tracking-wider text-[hsl(145,30%,70%)]">Tasks/Day</span>
                                <span class="text-center text-[10px] font-bold uppercase tracking-wider text-[hsl(145,30%,70%)]">Commission</span>
                            </div>
                            @foreach ($membershipChart as $row)
                                <div wire:key="chart-row-{{ $loop->index }}"
                                    class="grid grid-cols-4 gap-1 border-b border-[hsl(145,40%,15%)] px-4 py-3 transition-colors last:border-0 hover:bg-[hsl(145,40%,15%)]/30">
                                    <span class="text-sm font-bold text-[hsl(145,15%,85%)]">{{ $row['name'] }}</span>
                                    <span class="text-center text-sm font-bold text-[hsl(45,90%,65%)]">{{ $row['deposit'] }}</span>
                                    <span class="text-center text-sm font-bold text-[hsl(145,67%,65%)]">{{ $row['tasks'] }}</span>
                                    <span class="text-center text-sm font-bold text-[hsl(145,60%,60%)]">{{ $row['commission'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-2 pt-1">
                            <p class="text-center font-display text-xs font-bold uppercase tracking-wider text-[hsl(145,30%,70%)]">
                                Terms &amp; Conditions</p>
                            <div class="space-y-1.5">
                                @foreach ($chartTerms as $term)
                                    <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-[hsl(145,12%,60%)]">
                                        <span class="mt-px text-[hsl(145,60%,50%)]">&bull;</span>
                                        <span>{{ $term }}</span>
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl"
                    style="background: radial-gradient(at 50% 25%, rgb(22, 59, 38), rgb(6, 20, 12) 75%);">
                    <div class="pointer-events-none absolute inset-0 opacity-30"
                        style="background-image: radial-gradient(1px 1px at 12% 18%, white, transparent), radial-gradient(1px 1px at 42% 62%, white, transparent), radial-gradient(1px 1px at 78% 12%, white, transparent), radial-gradient(1px 1px at 88% 58%, white, transparent), radial-gradient(1px 1px at 22% 82%, white, transparent), radial-gradient(1px 1px at 58% 42%, white, transparent), radial-gradient(1.5px 1.5px at 65% 88%, rgb(246, 206, 85), transparent);">
                    </div>
                    <div class="pointer-events-none absolute inset-0 opacity-25"
                        style="background: conic-gradient(from 220deg at 50% 0%, transparent 0deg, rgba(39, 196, 104, 0.4) 25deg, transparent 50deg, transparent 180deg, rgba(244, 192, 37, 0.25) 200deg, transparent 230deg);">
                    </div>
                    <div class="relative z-10 space-y-5 px-6 pb-6 pt-7">
                        <div class="space-y-2 text-center">
                            <div class="flex justify-center">
                                <x-dashboard.icon name="crown" class="h-7 w-7 text-[hsl(45,90%,60%)]"
                                    style="filter: drop-shadow(0 0 8px hsl(45 90% 55% / 0.7))" />
                            </div>
                            <h2 class="font-display text-3xl font-bold uppercase leading-none tracking-tight text-white"
                                style="text-shadow: rgb(26, 102, 57) 0 2px 0, rgba(39, 196, 104, 0.6) 0 0 18px;">Deposit</h2>
                            <h2 class="font-display text-4xl font-bold uppercase leading-none tracking-tight"
                                style="background: linear-gradient(180deg, #fbd76a 0%, #f9bc06 60%, #c2750a 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; filter: drop-shadow(rgba(121, 73, 6, 0.6) 0 2px 4px) drop-shadow(rgba(244, 192, 37, 0.4) 0 0 14px);">
                                Bonuses!</h2>
                            <div class="inline-block rounded border border-[hsl(145,67%,46%)] px-4 py-1.5"
                                style="background: hsl(145 40% 10% / 0.6);">
                                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-[hsl(145,15%,90%)]">The more you deposit, the more you earn!</p>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            @foreach ($depositBonuses as $tier)
                                <div wire:key="bonus-{{ $loop->index }}"
                                    class="relative overflow-hidden rounded-xl border border-[hsl(145,50%,28%)]"
                                    style="background: linear-gradient(rgb(20, 52, 33) 0%, rgb(10, 31, 19) 100%); box-shadow: rgba(31, 122, 69, 0.5) 0 1px 0 inset, rgba(4, 16, 9, 0.6) 0 2px 8px;">
                                    <div class="flex items-stretch">
                                        <div class="flex items-center justify-center pl-3 pr-2">
                                            <div class="flex h-11 w-11 items-center justify-center rounded-full border border-[hsl(145,67%,46%)]"
                                                style="background: hsl(145 50% 10%);">
                                                <x-dashboard.icon :name="$tier['icon']" class="h-5 w-5 text-[hsl(145,67%,70%)]" />
                                            </div>
                                        </div>
                                        <div class="flex flex-1 flex-col justify-center py-2.5 pl-1 pr-2">
                                            <p class="text-[9px] font-bold uppercase leading-tight tracking-wider text-[hsl(145,15%,90%)]">
                                                {{ $tier['label'] }}</p>
                                            <p class="my-0.5 font-display text-xl font-bold leading-none"
                                                style="background: linear-gradient(180deg, #fbd76a 0%, #f2b90d 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">
                                                {{ $tier['range'] }}</p>
                                            <p class="text-[9px] font-bold uppercase leading-tight tracking-wider text-[hsl(145,15%,90%)]">
                                                Will Receive A</p>
                                        </div>
                                        <div class="my-2 w-px bg-[hsl(145,50%,28%)]"></div>
                                        <div class="flex min-w-[125px] items-center gap-2 px-3">
                                            <span class="font-display text-3xl font-bold leading-none"
                                                style="background: linear-gradient(180deg, #fbda74 0%, #f9bc06 55%, #b86f0a 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; filter: drop-shadow(rgba(97, 59, 5, 0.5) 0 1px 2px);">{{ $tier['bonus'] }}</span>
                                            <div class="leading-tight">
                                                <p class="text-[10px] font-bold uppercase text-[hsl(145,15%,92%)]">Deposit</p>
                                                <p class="text-[10px] font-bold uppercase text-[hsl(145,15%,92%)]">Bonus</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="relative pt-2">
                            <div class="flex items-center justify-center gap-3">
                                <x-dashboard.icon name="gift" class="h-7 w-7 text-[hsl(45,90%,60%)]"
                                    style="filter: drop-shadow(0 0 8px hsl(45 90% 55% / 0.6))" />
                                <div class="text-center">
                                    <p class="font-display text-base font-bold uppercase leading-tight tracking-tight"
                                        style="background: linear-gradient(180deg, #ffffff 0%, #c2d6ca 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; text-shadow: rgba(39, 196, 104, 0.4) 0 0 10px;">
                                        &#9733; Bigger Deposit. &#9733;</p>
                                    <p class="font-display text-lg font-bold uppercase leading-tight tracking-tight"
                                        style="background: linear-gradient(180deg, #fbd76a 0%, #f2b90d 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">
                                        Bigger Rewards!</p>
                                </div>
                                <x-dashboard.icon name="coins" class="h-7 w-7 text-[hsl(45,90%,60%)]"
                                    style="filter: drop-shadow(0 0 8px hsl(45 90% 55% / 0.6))" />
                            </div>
                            <div class="mt-2 flex items-center justify-center gap-1.5">
                                <x-dashboard.icon name="star" class="h-3 w-3 text-[hsl(45,90%,60%)]" />
                                <x-dashboard.icon name="star" class="h-3.5 w-3.5 text-[hsl(45,90%,60%)]" />
                                <x-dashboard.icon name="star" class="h-3 w-3 text-[hsl(45,90%,60%)]" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <button type="button" @click="open = open === 3 ? null : 3"
            class="flex w-full items-center gap-4 p-4 text-left transition-colors hover:bg-muted/40 sm:p-5">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                <x-dashboard.icon name="users" class="h-7 w-7 text-primary" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold uppercase tracking-wide text-foreground sm:text-base">Membership Levels</span>
                <span class="mt-0.5 block text-xs text-muted-foreground sm:text-sm">Full access details for each membership level</span>
            </span>
            <x-dashboard.icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted-foreground transition-transform"
                x-bind:class="open === 3 ? 'rotate-180' : ''" />
        </button>
        <div x-show="open === 3" x-cloak x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            class="border-t border-border px-4 pb-5 pt-4 sm:px-5">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($membershipLevelCards as $level)
                    <div wire:key="level-card-{{ $loop->index }}"
                        class="rounded-2xl border p-6 shadow-lg transition-all hover:shadow-xl"
                        style="border-color: {{ $level['border'] }}; background: hsl(220 25% 12%);">
                        <div class="mb-5 flex flex-col items-center text-center">
                            <img src="{{ asset($level['image']) }}" alt="{{ $level['name'] }} badge"
                                class="mb-3 h-20 w-20 object-contain drop-shadow-lg">
                            <h4 class="font-display text-lg font-bold text-white">{{ $level['name'] }}</h4>
                        </div>

                        <ul class="mb-5 space-y-2.5">
                            @foreach ($membershipPerks[$level['name']] as $perk)
                                <li wire:key="level-perk-{{ $level['name'] }}-{{ $loop->index }}" class="flex items-start gap-2 text-sm font-medium text-[hsl(220,10%,75%)]">
                                    <span class="mt-0.5 shrink-0 text-[hsl(220,10%,55%)]">&bull;</span>
                                    <span class="min-w-0 break-words">{{ $perk }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if ($level['progress'])
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs text-[hsl(220,10%,65%)]">
                                    <span>{{ $level['progress']['current'] }} / {{ $level['progress']['target'] }}</span>
                                    <span>{{ $level['progress']['percent'] }}%</span>
                                </div>
                                <div class="relative h-2 w-full overflow-hidden rounded-full bg-secondary">
                                    <div class="h-full bg-primary transition-all"
                                        style="width: {{ $level['progress']['percent'] }}%"></div>
                                </div>
                                <button type="button"
                                    wire:click="requestUpgrade('{{ $level['name'] }}')" wire:loading.attr="disabled"
                                    @disabled(! $level['progress']['can_request'])
                                    class="inline-flex h-9 w-full items-center justify-center whitespace-nowrap rounded-lg border text-sm font-bold text-white transition-colors hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                    style="background: {{ $level['accent'] }}; border-color: {{ $level['accent_border'] }};">
                                    {{ $level['progress']['button'] }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
