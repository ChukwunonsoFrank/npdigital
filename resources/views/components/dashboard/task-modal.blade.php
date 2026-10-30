@props([
    'pendingTask' => null,
    'commission' => 0,
    'commissionPercentage' => 0,
    'topUpAmount' => 0,
    'openEvent' => 'open-task-modal',
])

@php
    $commissionLabel = rtrim(rtrim(number_format((float) $commissionPercentage, 2, '.', ''), '0'), '.');
    $isMerge = (bool) ($pendingTask?->is_merge ?? false);
    $mergeItems = $isMerge ? ($pendingTask->merge_items ?? []) : [];
    $visibleMergeItems = array_slice($mergeItems, 0, 3);
    $remainingMergeItems = max(0, count($mergeItems) - 3);
    $mergeBrand = $isMerge ? (\App\Support\ProductBrand::label($pendingTask->brand) ?? 'Merged bundle') : null;
    $mergePosition = (int) ($pendingTask?->merge_position ?? 1);
    $topUpAmount = (int) $topUpAmount;
@endphp

<div x-data="{ open: false, rating: 0, review: '' }"
    x-on:{{ $openEvent }}.window="open = true; rating = 0; review = ''"
    x-on:close-task-modal.window="open = false"
    x-on:keydown.escape.window="open = false" {{ $attributes }}>
    <div x-cloak x-show="open" x-transition.opacity.duration.200ms class="fixed inset-0 z-50 bg-black/50"
        @click="open = false"></div>

    <div x-cloak x-show="open" role="dialog" aria-modal="true" aria-label="Task"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed left-1/2 top-1/2 z-50 max-h-[90vh] w-[calc(100%_-_2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-2xl bg-card shadow-xl">
        @if ($pendingTask)
            <div class="relative max-h-[90vh] overflow-y-auto p-5">
                <button type="button" @click="open = false" aria-label="Close"
                    class="absolute right-4 top-4 text-muted-foreground transition-colors hover:text-foreground">
                    <x-dashboard.icon name="x" class="h-5 w-5" />
                </button>

                @if ($isMerge)
                    <h2 class="pr-8 font-display text-lg font-bold text-foreground">Product optimization order</h2>

                    <div class="mt-4 flex gap-2">
                        @forelse ($visibleMergeItems as $item)
                            <div wire:key="merge-image-{{ $item['id'] ?? $loop->index }}"
                                class="relative h-24 flex-1 overflow-hidden rounded-xl bg-muted">
                                @if (! empty($item['image_url']) || ! empty($item['image']))
                                    <img src="{{ $item['image_url'] ?? \App\Support\ProductWarehouse::imageUrl($item['image'] ?? '') }}"
                                        alt="{{ $item['name'] ?? 'Merged product' }}" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center">
                                        <x-dashboard.icon name="package" class="h-6 w-6 text-muted-foreground" />
                                    </span>
                                @endif

                                @if ($loop->last && $remainingMergeItems > 0)
                                    <span
                                        class="absolute inset-0 flex items-center justify-center bg-foreground/60 font-display text-lg font-bold text-white">
                                        +{{ $remainingMergeItems }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="flex h-24 w-full items-center justify-center rounded-xl bg-muted">
                                <x-dashboard.icon name="package" class="h-6 w-6 text-muted-foreground" />
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="line-clamp-2 font-display text-lg font-bold leading-tight text-foreground"
                                title="{{ $pendingTask->title }}">{{ $pendingTask->title }}</h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $mergeBrand }}</p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-primary">
                            <x-dashboard.icon name="stack" class="h-3.5 w-3.5" />
                            Merge
                        </span>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-2 rounded-xl bg-muted/50 px-3 py-3 text-[11px]">
                        <span class="inline-flex items-center gap-1 font-bold text-amber-500">
                            @for ($star = 1; $star <= 5; $star++)
                                <x-dashboard.icon name="star" class="h-3.5 w-3.5" />
                            @endfor
                            <span class="ml-0.5 text-foreground">5.0</span>
                        </span>
                        <span class="text-muted-foreground">Task {{ $mergePosition }} / {{ \App\Support\ProductWarehouse::PRODUCTS_PER_SET }}</span>
                        <span class="text-muted-foreground">{{ $pendingTask->created_at->format('M d, Y') }}</span>
                    </div>

                    @if ($topUpAmount > 0)
                        <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-destructive/20 bg-destructive/10 px-3 py-2.5">
                            <span class="text-xs font-semibold text-destructive">Negative balance</span>
                            <span class="font-display text-sm font-bold text-destructive">-${{ number_format($topUpAmount / 100, 2) }}</span>
                        </div>
                    @endif

                    <div class="mt-4 flex items-center justify-between gap-4 rounded-xl bg-muted/50 px-3 py-3">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Product value</p>
                            <p class="mt-0.5 font-display text-sm font-bold text-foreground">
                                ${{ number_format((float) $pendingTask->cost, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Product profit</p>
                            <p class="mt-0.5 font-display text-sm font-bold text-emerald-600">
                                +${{ number_format((float) $commission, 2) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-destructive/20 bg-destructive/5 px-3 py-2.5">
                        <p class="text-xs leading-relaxed text-destructive">Merged order. Contact chat support to submit your
                            pending merge product.</p>
                    </div>

                    <button type="button" x-on:click="$wire.submitTask({{ $pendingTask->id }}, rating, review)"
                        wire:loading.attr="disabled" wire:target="submitTask"
                        class="mt-4 w-full rounded-xl bg-primary py-3 font-display text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">
                        <span wire:loading.remove wire:target="submitTask">Submit product</span>
                        <span wire:loading wire:target="submitTask">Submitting...</span>
                    </button>
                @else
                    <h2 class="px-6 text-center font-display text-lg font-bold text-foreground">{{ $pendingTask->title }}</h2>

                    <div class="relative mx-auto mt-4 h-44 w-44">
                        <img src="{{ \App\Support\ProductWarehouse::imageUrl($pendingTask->task_image_path) }}"
                            alt="{{ $pendingTask->title }}" class="h-full w-full rounded-lg object-cover">
                        <x-product-brand-logo :brand="$pendingTask->brand" />
                    </div>

                    <div class="mt-4 space-y-1 text-center text-sm">
                        <p class="text-foreground"><span class="text-muted-foreground">Cost:</span>
                            <span class="font-semibold text-foreground">${{ number_format((float) $pendingTask->cost, 2) }}</span>
                        </p>
                        <p class="text-foreground"><span class="text-muted-foreground">Profit:</span>
                            <span class="font-semibold text-emerald-600">${{ number_format((float) $commission, 2) }}</span>
                        </p>
                        <p class="text-foreground"><span class="text-muted-foreground">Commission:</span>
                            <span class="font-semibold">{{ $commissionLabel }}%</span>
                        </p>
                    </div>

                    <hr class="my-4 border-border">

                    <p class="text-center text-sm font-medium text-foreground">Rating (optional):</p>
                    <div class="mt-1 flex justify-center gap-1">
                        @for ($star = 1; $star <= 5; $star++)
                            <button type="button"
                                @click="rating = (rating === {{ $star }}) ? 0 : {{ $star }}"
                                aria-label="{{ $star }} star" class="transition-transform hover:scale-110">
                                <x-dashboard.icon name="star" x-show="rating >= {{ $star }}"
                                    class="h-6 w-6 text-amber-400" />
                                <x-dashboard.icon name="star-outline" x-show="rating < {{ $star }}"
                                    class="h-6 w-6 text-muted-foreground" />
                            </button>
                        @endfor
                    </div>

                    <div class="mt-4">
                        <p class="text-sm font-medium text-foreground">Review (optional):</p>
                        <textarea x-model="review" rows="3" placeholder="Share your thoughts..."
                            class="mt-1 w-full resize-none rounded-lg border border-input bg-background p-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30"></textarea>
                    </div>

                    <button type="button" x-on:click="$wire.submitTask({{ $pendingTask->id }}, rating, review)"
                        wire:loading.attr="disabled" wire:target="submitTask"
                        class="mt-4 w-full rounded-xl bg-primary py-3 font-display text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">
                        <span wire:loading.remove wire:target="submitTask">Submit Task</span>
                        <span wire:loading wire:target="submitTask">Submitting...</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
