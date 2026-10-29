<div x-data>
    <div class="animate-fade-in space-y-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" wire:navigate aria-label="Back to dashboard"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-md transition-colors hover:bg-muted">
                    <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
                </a>
                <h1 class="font-display text-xl font-bold">Starting</h1>
            </div>

            <span
                class="inline-flex items-center gap-1.5 rounded-full border-[1.5px] px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.12em] shadow-sm"
                style="color: hsl(var(--membership-gold)); border-color: hsl(var(--membership-gold) / 0.5); background: linear-gradient(135deg, hsl(var(--membership-gold) / 0.18), hsl(var(--membership-gold) / 0.06)); box-shadow: 0 2px 10px -2px hsl(var(--membership-gold) / 0.35), inset 0 1px 0 0 hsl(var(--membership-gold) / 0.35);">
                <x-dashboard.icon name="crown" class="h-3.5 w-3.5" />
                VIP &middot; {{ $user->membership_level ?? 'Normal' }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div
                class="relative flex min-h-[120px] flex-col justify-between overflow-hidden rounded-2xl border-[2.5px] border-primary/30 bg-gradient-to-br from-primary/10 via-primary/5 to-background p-5 shadow-lg shadow-primary/20">
                <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-primary/20 blur-2xl">
                </div>
                <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                    style="background-image: radial-gradient(var(--color-foreground) 1px, transparent 1px); background-size: 14px 14px;">
                </div>

                <div class="relative flex items-start gap-2.5">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary/70 shadow-lg shadow-primary/30 ring-2 ring-primary/20">
                        <x-dashboard.icon name="wallet" class="h-5 w-5 text-primary-foreground" />
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-muted-foreground">Balance</p>
                        </div>
                    </div>
                </div>

                <p class="relative mt-3 font-display font-bold leading-none tracking-tight text-foreground">
                    <span class="mr-0.5 align-top text-[10px] text-muted-foreground">USD</span>
                    <span
                        class="inline-block whitespace-nowrap leading-none">${{ number_format(($user->balance ?? 0) / 100, 2) }}</span>
                </p>
            </div>

            <div class="relative flex min-h-[120px] flex-col justify-between overflow-hidden rounded-2xl border-[2.5px] p-5"
                style="border-color: hsl(var(--membership-gold) / 0.4); background: linear-gradient(135deg, hsl(var(--membership-gold) / 0.18), hsl(var(--membership-gold) / 0.05) 60%, var(--color-background)); box-shadow: 0 8px 24px -8px hsl(var(--membership-gold) / 0.35), inset 0 1px 0 0 hsl(var(--membership-gold) / 0.3);">
                <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full blur-2xl"
                    style="background-color: hsl(var(--membership-gold) / 0.25);"></div>
                <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                    style="background-image: radial-gradient(var(--color-foreground) 1px, transparent 1px); background-size: 14px 14px;">
                </div>

                <div class="relative flex items-start gap-2.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                        style="background: linear-gradient(135deg, hsl(var(--membership-gold)), hsl(var(--membership-gold) / 0.7)); box-shadow: 0 6px 16px -4px hsl(var(--membership-gold) / 0.5);">
                        <x-dashboard.icon name="clock" class="h-5 w-5 text-white" />
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full"
                                style="background-color: hsl(var(--membership-gold));"></span>
                            <p class="text-[9px] font-bold uppercase tracking-[0.12em]"
                                style="color: hsl(var(--membership-gold));">On Hold</p>
                        </div>
                    </div>
                </div>

                <p class="relative mt-3 font-display font-bold leading-none tracking-tight"
                    style="color: hsl(var(--membership-gold));">
                    <span class="mr-0.5 align-top text-[10px] opacity-70">USD</span>
                    <span
                        class="inline-block whitespace-nowrap leading-none">${{ number_format((($user->lien_status ?? 'off_hold') === 'on_hold' ? (int) $user->lien_amount : 0) / 100, 2) }}</span>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('dashboard.bonus-center') }}" wire:navigate
                class="group flex min-h-[96px] flex-col justify-between rounded-2xl border border-border bg-card p-4 shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <div class="flex items-start justify-between gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                        <x-dashboard.icon name="gift" class="h-4 w-4" />
                    </span>
                    <x-dashboard.icon name="arrow-up-right" class="h-4 w-4 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
                <div class="mt-3">
                    <p class="font-display text-sm font-bold text-foreground">Bonus Center</p>
                    <p class="text-[11px] text-muted-foreground">Daily sign-in rewards</p>
                </div>
            </a>

            <a href="{{ route('dashboard.vip-levels') }}" wire:navigate
                class="group flex min-h-[96px] flex-col justify-between rounded-2xl border border-border bg-card p-4 shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <div class="flex items-start justify-between gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <x-dashboard.icon name="crown" class="h-4 w-4" />
                    </span>
                    <x-dashboard.icon name="arrow-up-right" class="h-4 w-4 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
                <div class="mt-3">
                    <p class="font-display text-sm font-bold text-foreground">VIP Levels</p>
                    <p class="text-[11px] text-muted-foreground">{{ $user->membership_level ?? 'Normal' }} · View all levels</p>
                </div>
            </a>
        </div>

        <div>
            <h3 class="mb-3 font-display text-base font-semibold">Top Rated &amp; Best Selling Products</h3>
            <div class="overflow-hidden rounded-xl border bg-card py-4">
                <div class="product-carousel-track">
                    @foreach (array_merge($products, $products) as $index => $product)
                        <div wire:key="product-{{ $index }}"
                            class="card-elevated group mx-3 w-44 flex-shrink-0 overflow-hidden rounded-xl border bg-card text-left">
                            <div class="relative h-28 overflow-hidden">
                                <img src="{{ $product['image'] }}" alt="{{ $product['title'] }}" loading="lazy"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110">
                                <span
                                    class="absolute left-1.5 top-1.5 rounded-full bg-accent px-1.5 py-0.5 text-[8px] font-bold uppercase text-accent-foreground">{{ $product['promo'] }}</span>
                                <x-product-brand-logo :brand="$product['brand']" size="sm" />
                            </div>
                            <div class="space-y-1.5 p-2.5">
                                <p class="line-clamp-1 font-display text-[11px] font-semibold">{{ $product['title'] }}</p>
                                <div class="flex items-center gap-1">
                                    <x-dashboard.icon name="star" class="h-3 w-3 text-amber-400" />
                                    <span class="text-[10px] font-bold text-foreground">{{ $product['rating'] }}</span>
                                    <span class="text-[9px] text-muted-foreground">({{ $product['reviews'] }})</span>
                                </div>
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <span class="flex items-center gap-0.5 text-[9px]">
                                        <x-dashboard.icon name="heart"
                                            class="h-2.5 w-2.5 fill-destructive text-destructive" />
                                        {{ $product['likes'] }}
                                    </span>
                                    <span class="flex items-center gap-0.5 text-[9px]">
                                        <x-dashboard.icon name="eye" class="h-2.5 w-2.5" />
                                        {{ $product['sold'] }} sold
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-primary">{{ $product['price'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @php
            $setProgress = $user->productsPerSet() > 0 ? min(100, (int) floor(($user->tasks_completed ?? 0) / $user->productsPerSet() * 100)) : 0;
            $dailyComplete = $setsCompleted >= \App\Models\User::DAILY_TASK_SETS;
        @endphp
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-display text-sm font-bold text-foreground">Daily progress</h3>
                    <p class="text-[11px] text-muted-foreground">{{ $setsCompleted }}/{{ \App\Models\User::DAILY_TASK_SETS }} sets completed today</p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-primary">
                    <x-dashboard.icon name="clock" class="h-3 w-3" /> Resets daily
                </span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2">
                @for ($set = 1; $set <= \App\Models\User::DAILY_TASK_SETS; $set++)
                    @php
                        $isDone = $set <= $setsCompleted;
                        $isCurrent = $set === $setsCompleted + 1;
                    @endphp
                    <div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div @class(['h-full rounded-full bg-primary transition-all', 'bg-primary/60' => $isCurrent && ! $isDone]) style="width: {{ $isDone ? 100 : ($isCurrent ? $setProgress : 0) }}%"></div>
                        </div>
                        <p @class(['mt-1.5 flex items-center gap-1 text-[10px] font-semibold', 'text-primary' => $isDone || $isCurrent, 'text-muted-foreground' => ! $isDone && ! $isCurrent])>
                            @if ($isDone)
                                <x-dashboard.icon name="check" class="h-3 w-3" />
                            @endif
                            Set {{ $set }}
                        </p>
                    </div>
                @endfor
            </div>

            @if (! $dailyComplete)
                <div class="mt-4 flex items-center justify-between rounded-xl bg-muted/40 px-3 py-2">
                    <span class="text-xs text-muted-foreground">Set {{ $setsCompleted + 1 }}</span>
                    <span class="text-xs font-bold text-foreground">{{ $user->tasks_completed ?? 0 }} / {{ $user->productsPerSet() }} products</span>
                </div>
            @endif

            <button type="button" wire:click="startTask" @disabled($dailyComplete)
                class="mt-4 inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-8 py-3 font-display text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">
                @if ($dailyComplete)
                    Daily limit reached
                @else
                    START TASK
                @endif
            </button>
        </div>

        <div class="space-y-2 rounded-xl border bg-card p-4">
            <h3 class="font-display text-sm font-bold">Operating Hours</h3>
            <p class="text-sm text-muted-foreground">&bull; Users may optimize the Product during the daily operation hours
                from 10:00 to 22:59</p>
            <div class="rounded-lg border bg-accent/10 p-3 text-center">
                <p class="text-xs text-muted-foreground"><strong
                        class="font-semibold text-foreground">Notice:</strong> For further explanation, please click
                    "Support" on the platform to contact our online customer service</p>
            </div>
        </div>
    </div>

    <x-dashboard.task-modal :pending-task="$pendingTask" :commission="$commission"
        :commission-percentage="$commissionPercentage" :rating="$rating" :show-pending-alert="$showPendingAlert"
        :top-up-amount="$topUpAmount" />
</div>

<script>
    let lastToast = null;

    function toast(message) {
        if (lastToast) {
            lastToast.hideToast();
        }

        const copiedToastMarkup = `
            <div class="flex items-start p-2">
                <div class="shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6236ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big-icon lucide-circle-check-big"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                </div>
                <div class="ms-1 flex-1">
                    <p class="text-xs font-semibold text-black" style="margin-bottom: 0 !important;">${message}</p>
                </div>
            </div>
        `;

        lastToast = Toastify({
            text: copiedToastMarkup,
            className: "fixed top-1 start-1/2 -translate-x-1/2 z-[1000] w-4/5 md:w-1/2 lg:w-1/4 bg-white text-sm text-black rounded-xl shadow-lg [&>.toast-close]:hidden",
            duration: 4000,
            close: false,
            escapeMarkup: false
        });

        lastToast.showToast();
    }
</script>

@script
    <script>
        $wire.on('task-completed', (event) => {
            toast(event.message)
        });

        $wire.on('task-limit-reached', (event) => {
            toast(event.message)
        });

        $wire.on('insufficient-balance', (event) => {
            toast(event.message)
        });

        $wire.on('deposit-required', (event) => {
            toast(event.message)
        });

        $wire.on('minimum-balance-required', (event) => {
            toast(event.message)
        });

        $wire.on('upgrade-required', (event) => {
            toast(event.message)
        });
    </script>
@endscript
