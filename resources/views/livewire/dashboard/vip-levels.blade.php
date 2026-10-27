<div class="mx-auto max-w-5xl space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.start') }}" wire:navigate aria-label="Back to start"
            class="inline-flex h-10 w-10 items-center justify-center rounded-md transition-colors hover:bg-muted">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <div>
            <h1 class="font-display text-2xl font-bold">VIP Levels</h1>
            <p class="text-sm text-muted-foreground">Every level gives you 3 sets of {{ $productsPerSet }} products a day. Higher levels earn more per product.</p>
        </div>
    </div>

    <section class="relative overflow-hidden rounded-2xl border border-primary/30 bg-gradient-to-br from-primary/15 via-primary/5 to-card p-5 shadow-sm">
        <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-primary/20 blur-2xl"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/70 text-primary-foreground shadow-lg shadow-primary/30">
                    <x-dashboard.icon name="crown" class="h-7 w-7" />
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-primary">Your current level</p>
                    <h2 class="font-display text-2xl font-bold">{{ $currentLevel?->name ?? auth()->user()->membership_level ?? 'Normal' }}</h2>
                    <p class="text-sm text-muted-foreground">{{ rtrim(rtrim(number_format((float) ($currentLevel?->percentage ?? 0), 2, '.', ''), '0'), '.') }}% commission per product</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="rounded-xl bg-background/70 px-4 py-2">
                    <p class="text-[10px] uppercase text-muted-foreground">Balance</p>
                    <p class="font-display text-lg font-bold">${{ number_format($balance / 100, 2) }}</p>
                </div>
                <div class="rounded-xl bg-background/70 px-4 py-2">
                    <p class="text-[10px] uppercase text-muted-foreground">Per set</p>
                    <p class="font-display text-lg font-bold">{{ $productsPerSet }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $card)
            <section wire:key="vip-level-{{ $card['name'] }}"
                @class([
                    'relative flex flex-col overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-colors',
                    'border-primary bg-primary/5 ring-1 ring-primary/30' => $card['is_current'],
                    'border-border' => ! $card['is_current'],
                ])>
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span @class([
                            'flex h-10 w-10 items-center justify-center rounded-xl',
                            'bg-primary text-primary-foreground' => $card['is_current'],
                            'bg-primary/10 text-primary' => ! $card['is_current'],
                        ])>
                            <x-dashboard.icon name="crown" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="font-display text-lg font-bold">{{ $card['name'] }}</h3>
                            <p class="text-xs text-muted-foreground">{{ $card['percentage'] }}% commission</p>
                        </div>
                    </div>
                    @if ($card['is_current'])
                        <span class="rounded-full bg-primary px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide text-primary-foreground">Current</span>
                    @elseif ($card['is_reached'])
                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide text-emerald-600">Unlocked</span>
                    @else
                        <span class="rounded-full bg-muted px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide text-muted-foreground">Locked</span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-muted/40 p-2.5">
                        <p class="text-[10px] uppercase text-muted-foreground">Minimum balance</p>
                        <p class="text-sm font-bold">{{ $card['minimum'] ? '$'.number_format($card['minimum'] / 100, 0) : '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-muted/40 p-2.5">
                        <p class="text-[10px] uppercase text-muted-foreground">Products per set</p>
                        <p class="text-sm font-bold">{{ $productsPerSet }} products</p>
                    </div>
                </div>

                @if (! $card['is_current'] && $card['minimum'])
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-[10px] text-muted-foreground">
                            <span>Balance progress</span>
                            <span>{{ $card['progress'] }}%</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $card['progress'] }}%"></div>
                        </div>
                    </div>
                @endif

                <ul class="mt-4 space-y-1.5 text-xs text-muted-foreground">
                    @foreach (array_slice($card['perks'], 0, 3) as $perk)
                        <li class="flex gap-1.5">
                            <x-dashboard.icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
                            <span>{{ $perk }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($card['next'])
                    <a href="{{ route('dashboard.activities') }}" wire:navigate
                        class="mt-auto inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-primary/30 text-xs font-bold text-primary transition-colors hover:bg-primary/5">
                        Request upgrade <x-dashboard.icon name="arrow-up-right" class="h-3.5 w-3.5" />
                    </a>
                @endif
            </section>
        @endforeach
    </div>

    <section class="rounded-2xl border border-border bg-card p-5">
        <h3 class="font-display text-sm font-bold">Fair sets at every level</h3>
        <p class="mt-1 text-sm text-muted-foreground">All membership levels include 3 daily sets of {{ $productsPerSet }} products. Your level changes your commission rate and rewards, not the number of products you receive.</p>
    </section>
</div>
