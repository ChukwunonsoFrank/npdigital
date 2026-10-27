@props([
    'pendingTask' => null,
    'commission' => 0,
    'commissionPercentage' => 0,
    'rating' => 0,
    'showPendingAlert' => false,
    'topUpAmount' => 0,
])

@php
    $commissionLabel = rtrim(rtrim(number_format((float) $commissionPercentage, 2, '.', ''), '0'), '.');
    $isMerge = (bool) ($pendingTask?->is_merge ?? false);
    $mergeItems = $isMerge ? ($pendingTask->merge_items ?? []) : [];
    $topUpAmount = (int) $topUpAmount;
@endphp

<div x-cloak x-show="$wire.showTaskModal" x-transition.opacity.duration.200ms class="fixed inset-0 z-50 bg-black/50"
    @click="$wire.closeTaskModal()"></div>

<div x-cloak x-show="$wire.showTaskModal" role="dialog" aria-modal="true" aria-label="Task"
    x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition duration-200 ease-in"
    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
    class="fixed left-1/2 top-1/2 z-50 max-h-[90vh] w-[calc(100%_-_2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-2xl bg-card shadow-xl">
    @if ($pendingTask)
        @if ($showPendingAlert)
            <div
                class="flex items-center justify-center gap-1.5 bg-amber-50 py-2.5 text-[11px] font-bold uppercase tracking-[0.12em] text-amber-600">
                <x-dashboard.icon name="triangle-alert" class="h-4 w-4" />
                Pending Product
            </div>
        @endif

        <div class="relative max-h-[calc(90vh_-_44px)] overflow-y-auto p-5">
            <button type="button" wire:click="closeTaskModal" aria-label="Close"
                class="absolute right-4 top-4 text-muted-foreground transition-colors hover:text-foreground">
                <x-dashboard.icon name="x" class="h-5 w-5" />
            </button>

            @if ($isMerge)
                <div class="flex justify-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-primary">
                        <x-dashboard.icon name="merge" class="h-3.5 w-3.5" />
                        Merge product
                    </span>
                </div>
            @endif

            <h2 class="mt-2 px-6 text-center font-display text-lg font-bold text-foreground">{{ $pendingTask->title }}</h2>

            @if ($isMerge)
                <div class="mt-4 space-y-2 rounded-xl border border-border bg-muted/20 p-3">
                    @foreach ($mergeItems as $item)
                        <div wire:key="merge-item-{{ $item['id'] ?? $loop->index }}" class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted">
                                @if (! empty($item['image_url']) || ! empty($item['image']))
                                    <img src="{{ $item['image_url'] ?? \App\Support\ProductWarehouse::imageUrl($item['image'] ?? '') }}"
                                        alt="" class="h-full w-full object-cover">
                                @else
                                    <x-dashboard.icon name="package" class="h-4 w-4 text-muted-foreground" />
                                @endif
                            </span>
                            <span class="min-w-0 flex-1 truncate text-xs font-medium text-foreground">{{ $item['name'] }}</span>
                            <span class="shrink-0 text-xs font-bold text-foreground">${{ number_format((float) ($item['price'] ?? 0), 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="relative mx-auto mt-4 h-44 w-44">
                    <img src="{{ \App\Support\ProductWarehouse::imageUrl($pendingTask->task_image_path) }}"
                        alt="{{ $pendingTask->title }}" class="h-full w-full rounded-lg object-cover">
                    <x-product-brand-logo :brand="$pendingTask->brand" />
                </div>
            @endif

            <div class="mt-4 space-y-1 text-center text-sm">
                <p class="text-foreground"><span class="text-muted-foreground">{{ $isMerge ? 'Combined cost' : 'Cost' }}:</span>
                    <span class="font-semibold text-foreground">${{ number_format((float) $pendingTask->cost, 2) }}</span>
                </p>
                <p class="text-foreground"><span class="text-muted-foreground">{{ $isMerge ? 'You earn' : 'Profit' }}:</span>
                    <span class="font-semibold text-emerald-600">${{ number_format((float) $commission, 2) }}</span>
                </p>
                <p class="text-foreground"><span class="text-muted-foreground">Commission:</span>
                    <span class="font-semibold">{{ $commissionLabel }}%</span>
                </p>
            </div>

            @if ($isMerge && $topUpAmount > 0)
                <div class="mt-4 rounded-xl border border-destructive/30 bg-destructive/5 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-destructive">Top-up required</span>
                        <span class="font-display text-xl font-bold text-destructive">-${{ number_format($topUpAmount / 100, 2) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">Your wallet balance does not cover this merge yet. Top up at least this amount to submit the merged products.</p>
                </div>
            @endif

            <hr class="my-4 border-border">

            <p class="text-center text-sm font-medium text-foreground">Rating (optional):</p>
            <div class="mt-1 flex justify-center gap-1">
                @for ($star = 1; $star <= 5; $star++)
                    <button type="button" wire:click="setRating({{ $star }})" aria-label="{{ $star }} star"
                        class="transition-transform hover:scale-110">
                        <x-dashboard.icon :name="$star <= $rating ? 'star' : 'star-outline'"
                            class="h-6 w-6 {{ $star <= $rating ? 'text-amber-400' : 'text-muted-foreground' }}" />
                    </button>
                @endfor
            </div>

            <div class="mt-4">
                <p class="text-sm font-medium text-foreground">Review (optional):</p>
                <textarea wire:model="review" rows="3" placeholder="Share your thoughts..."
                    class="mt-1 w-full resize-none rounded-lg border border-input bg-background p-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30"></textarea>
            </div>

            @if ($isMerge && $topUpAmount > 0)
                <a href="{{ route('dashboard.deposit') }}" wire:navigate
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 font-display text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                    <x-dashboard.icon name="wallet" class="h-4 w-4" />
                    Top Up Wallet
                </a>
            @else
                <button type="button" wire:click="submitTask"
                    class="mt-4 w-full rounded-xl bg-primary py-3 font-display text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                    Submit Task
                </button>
            @endif
        </div>
    @endif
</div>
