<div class="space-y-4 animate-fade-in" x-on:toast.self="window.showToast($event.detail.message)">
    @php
        $mergeCount = count($mergeOrders);
        $normalCount = max((int) $totalOrders - $mergeCount, 0);
    @endphp

    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-3">
            @if ($selectedUser)
                <button type="button" wire:click="changeUser" aria-label="Back to user selection"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
                </button>
            @else
                <a href="{{ route('admin.dashboard') }}" aria-label="Back to overview"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
                </a>
            @endif
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-bold">Assign Orders</h1>
                <p class="text-sm text-muted-foreground">Build a user's full order set, with merge orders placed exactly where you want them.</p>
            </div>
        </div>
    </div>

    @if (! $selectedUser)
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg font-bold">Select a user</h2>
                    <p class="text-sm text-muted-foreground">Search for the account that should receive the order set.</p>
                </div>
                @if ($users->isNotEmpty())
                    <span class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">{{ $users->count() }} shown</span>
                @endif
            </div>

            <div class="relative mt-4">
                <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input wire:model.live.debounce.300ms="userSearch" type="search" aria-label="Search users"
                    placeholder="Search by name, username, or email..."
                    class="h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-base placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:text-sm" />
            </div>

            <div class="mt-4 divide-y divide-border overflow-hidden rounded-lg border border-border">
                @forelse ($users as $user)
                    <button type="button" wire:key="assign-user-{{ $user->id }}" wire:click="selectUser({{ $user->id }})"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                            {{ $user->initials() ?: strtoupper(substr($user->username, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-semibold text-foreground">{{ $user->name ?: $user->username }}</span>
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">{{ ucfirst($user->membership_level ?: 'Normal') }}</span>
                                @if ($user->active_orders_count > 0)
                                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">{{ $user->active_orders_count }} active</span>
                                @endif
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-muted-foreground">
                                {{ '@'.$user->username }} &middot; ${{ number_format($user->balance / 100, 2) }} balance
                            </span>
                        </span>
                        <x-dashboard.icon name="chevron-right" class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </button>
                @empty
                    <p class="p-6 text-center text-sm text-muted-foreground">No users match your search.</p>
                @endforelse
            </div>

            @if ($users->count() === 50)
                <p class="mt-3 text-xs text-muted-foreground">Showing the first 50 results. Search to narrow the list.</p>
            @endif
        </div>
    @else
        @php
            $name = $selectedUser->name ?: $selectedUser->username;
            $level = strtolower($selectedUser->membership_level);
            $normalCommissionLabel = rtrim(rtrim(number_format($normalCommission, 2, '.', ''), '0'), '.');
            $mergeCommissionLabel = rtrim(rtrim(number_format($mergePercentage, 2, '.', ''), '0'), '.');
            $setProgress = (int) $selectedUser->tasks_completed;
            $setSize = $selectedUser->productsPerSet();
            $setAfterAssignment = $setProgress + (int) $totalOrders;
        @endphp

        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                    {{ $selectedUser->initials() ?: strtoupper(substr($selectedUser->username, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-display text-lg font-bold">{{ $name }}</p>
                        <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">{{ $selectedUser->is_training ? 'Virtual' : 'Real' }}</span>
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">{{ ucfirst($selectedUser->membership_level ?: 'Normal') }}</span>
                    </div>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ '@'.$selectedUser->username }} &middot; Balance ${{ number_format($selectedUser->balance / 100, 2) }}
                        &middot; Set {{ $setProgress }} / {{ $setSize }}
                    </p>
                </div>
                <button type="button" wire:click="changeUser"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="users" class="h-3.5 w-3.5" /> Change user
                </button>
            </div>
            @error('userId') <p class="mt-2 text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Member level</p>
                <p class="mt-1 font-display text-xl font-bold">{{ ucfirst($selectedUser->membership_level ?: 'Normal') }} Level</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Normal commission</p>
                <p class="mt-1 font-display text-xl font-bold">{{ $normalCommissionLabel }}%</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Merge multiplier</p>
                <p class="mt-1 font-display text-xl font-bold">{{ $mergeCommissionLabel }}%</p>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-bold">Assign task orders</h2>
                    <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                        Normal orders and amounts come from the {{ ucfirst($selectedUser->membership_level ?: 'Normal') }} warehouse.
                        Merge orders replace one normal order at their chosen position; pick their products and the merge commission and negative balance are calculated automatically.
                    </p>
                </div>
            </div>

            @if (! $selectedUser->is_training && ! $selectedUser->has_made_first_deposit)
                <div class="mt-4 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-500/5 p-3 text-sm text-muted-foreground">
                    <x-dashboard.icon name="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                    <p>This user has not made their first deposit, so they cannot start these orders until they top up.</p>
                </div>
            @else
                <div class="mt-4 flex items-start gap-3 rounded-lg border border-primary/20 bg-primary/5 p-3 text-sm text-muted-foreground">
                    <x-dashboard.icon name="sparkles" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    <p>Normal orders are picked from the user's level. Choose the products in each merge bundle; the commission and negative balance are calculated automatically.</p>
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)]">
                <div class="space-y-2">
                    <label for="total-orders" class="text-sm font-medium text-foreground">Total orders</label>
                    <input wire:model.live.debounce.500ms="totalOrders" id="total-orders" type="number" min="1" max="{{ $maxOrders }}"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm font-semibold text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring @error('totalOrders') border-destructive @enderror" />
                    <p class="text-xs text-muted-foreground">
                        The normal set is 30. Increase or reduce it for this assignment.
                        @if ($setProgress > 0)
                            {{ $setProgress }} already submitted; this set will hold {{ $setAfterAssignment }} orders after assigning.
                        @else
                            This set will hold {{ $setAfterAssignment }} orders.
                        @endif
                    </p>
                    @error('totalOrders') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-3 gap-2 self-end rounded-xl border border-border bg-muted/30 p-3 text-center">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Normal</p>
                        <p class="font-display text-xl font-bold">{{ $normalCount }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Merge</p>
                        <p class="font-display text-xl font-bold text-primary">{{ $mergeCount }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Total</p>
                        <p class="font-display text-xl font-bold">{{ $totalOrders }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-foreground">Merge orders</h3>
                        <p class="text-xs text-muted-foreground">Choose the order number each merge lands on, then pick the products in its bundle. Multiple merges are allowed.</p>
                    </div>
                    <button type="button" wire:click="addMergeOrder"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <x-dashboard.icon name="plus" class="h-3.5 w-3.5" /> Add merge order
                    </button>
                </div>

                @error('mergeOrders') <p class="mt-2 text-xs text-destructive" role="alert">{{ $message }}</p> @enderror

                <div class="mt-3 space-y-3">
                    @forelse ($mergeOrders as $index => $mergeOrder)
                        @php
                            $mergeCostCents = (int) round(array_sum(array_map(fn (array $item): float => (float) $item['price'], $mergeOrder['items'])) * 100);
                            $mergeCommissionCents = (int) round($mergeCostCents * $mergePercentage / 100);
                            $mergeNegativeCents = max(0, $mergeCostCents - (int) $selectedUser->balance);
                            $isPickerOpen = $productPickerIndex === $index;
                        @endphp
                        <div wire:key="merge-order-{{ $mergeOrder['key'] }}" class="rounded-xl border border-border bg-muted/20 p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[8rem_minmax(0,1fr)_minmax(0,1fr)_auto]">
                                <div class="space-y-1">
                                    <label for="merge-position-{{ $mergeOrder['key'] }}" class="text-xs font-medium text-foreground">Order #</label>
                                    <input wire:model="mergeOrders.{{ $index }}.position" id="merge-position-{{ $mergeOrder['key'] }}" type="number" min="1" max="{{ $totalOrders }}"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm font-semibold text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring @error('mergeOrders.'.$index.'.position') border-destructive @enderror" />
                                    @error('mergeOrders.'.$index.'.position') <p class="text-[10px] text-destructive" role="alert">{{ $message }}</p> @enderror
                                </div>

                                <div class="space-y-1">
                                    <span class="text-xs font-medium text-foreground">Commission ({{ $mergeCommissionLabel }}%)</span>
                                    <div class="flex h-10 items-center justify-between rounded-md border border-border bg-muted/40 px-3">
                                        <span class="text-sm font-semibold text-foreground">${{ number_format($mergeCommissionCents / 100, 2) }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Auto</span>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-xs font-medium text-foreground">Negative balance</span>
                                    <div class="flex h-10 items-center justify-between rounded-md border border-border bg-muted/40 px-3">
                                        <span @class([
                                            'text-sm font-semibold',
                                            'text-destructive' => $mergeNegativeCents > 0,
                                            'text-foreground' => $mergeNegativeCents === 0,
                                        ])>{{ $mergeNegativeCents > 0 ? '-$'.number_format($mergeNegativeCents / 100, 2) : '$0.00' }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Auto</span>
                                    </div>
                                </div>

                                <div class="flex items-end">
                                    <button type="button" wire:click="removeMergeOrder({{ $index }})"
                                        class="inline-flex h-10 w-full items-center justify-center gap-1 rounded-md border border-destructive/30 px-3 text-xs font-medium text-destructive transition-colors hover:bg-destructive/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" /> Remove
                                    </button>
                                </div>
                            </div>

                            @error('mergeOrders.'.$index.'.items') <p class="mt-2 text-[10px] text-destructive" role="alert">{{ $message }}</p> @enderror

                            <div class="mt-3 border-t border-border/60 pt-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Bundle</span>
                                    @forelse ($mergeOrder['items'] as $itemIndex => $item)
                                        <span wire:key="merge-item-{{ $mergeOrder['key'] }}-{{ $item['id'] }}" class="inline-flex max-w-full items-center gap-1 rounded-full bg-secondary py-1 pr-1 pl-2 text-[10px] font-semibold text-secondary-foreground">
                                            <span class="truncate">{{ $item['name'] }}</span>
                                            <span class="shrink-0 font-bold">${{ number_format((float) $item['price'], 2) }}</span>
                                            <button type="button" wire:click="removeMergeProduct({{ $index }}, {{ $itemIndex }})" aria-label="Remove {{ $item['name'] }} from bundle"
                                                class="rounded-full p-0.5 text-secondary-foreground/70 transition-colors hover:bg-destructive/10 hover:text-destructive focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <x-dashboard.icon name="x" class="h-3 w-3" />
                                            </button>
                                        </span>
                                    @empty
                                        <span class="text-[10px] font-semibold text-muted-foreground">No products selected yet.</span>
                                    @endforelse
                                    <button type="button" wire:click="toggleProductPicker({{ $index }})"
                                        class="ml-auto inline-flex h-8 items-center justify-center gap-1 rounded-md border border-input bg-background px-2.5 text-[10px] font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <x-dashboard.icon :name="$isPickerOpen ? 'x' : 'plus'" class="h-3 w-3" />
                                        {{ $isPickerOpen ? 'Close picker' : 'Add product' }}
                                    </button>
                                </div>

                                @if ($isPickerOpen)
                                    <div class="mt-3 rounded-lg border border-border bg-background p-3">
                                        <div class="relative">
                                            <x-dashboard.icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <input wire:model.live.debounce.300ms="productSearch" id="merge-product-search-{{ $mergeOrder['key'] }}" type="search"
                                                aria-label="Search bundle products" placeholder="Search all warehouse products..."
                                                class="h-9 w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-base placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:text-sm" />
                                        </div>
                                        <p class="mt-1.5 text-[10px] text-muted-foreground">Search across every warehouse level, then add the products this bundle should contain.</p>
                                        @error('productSearch') <p class="mt-1.5 text-[10px] text-destructive" role="alert">{{ $message }}</p> @enderror

                                        <div class="mt-2 max-h-64 divide-y divide-border overflow-y-auto rounded-md border border-border">
                                            @forelse ($productResults as $product)
                                                <button type="button" wire:key="merge-product-{{ $mergeOrder['key'] }}-{{ $product['id'] }}"
                                                    wire:click="addMergeProduct({{ $index }}, '{{ $product['id'] }}')"
                                                    class="flex w-full items-center gap-2 px-2.5 py-2 text-left transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted">
                                                        @if ($product['image_url'])
                                                            <img src="{{ $product['image_url'] }}" alt="" class="h-full w-full object-cover" />
                                                        @else
                                                            <x-dashboard.icon name="package" class="h-4 w-4 text-muted-foreground" />
                                                        @endif
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-xs font-semibold text-foreground">{{ $product['name'] }}</span>
                                                        <span class="block text-[10px] text-muted-foreground">{{ ucfirst($product['level']) }} &middot; Day {{ $product['day'] }} &middot; Group {{ strtoupper($product['group']) }}</span>
                                                    </span>
                                                    <span class="shrink-0 text-xs font-bold text-foreground">${{ number_format((float) $product['price'], 2) }}</span>
                                                    <x-dashboard.icon name="plus" class="h-4 w-4 shrink-0 text-primary" />
                                                </button>
                                            @empty
                                                <p class="p-3 text-xs text-muted-foreground">No products match your search.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-2 flex items-center justify-end">
                                    <span class="text-xs font-bold text-foreground">Combined ${{ number_format($mergeCostCents / 100, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-border p-4 text-center text-sm text-muted-foreground">
                            No merge orders. The full set will be normal products.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 space-y-3 border-t border-border pt-5">
                <label class="flex items-start gap-2 text-sm text-foreground">
                    <input type="checkbox" wire:model="clearExisting"
                        class="mt-0.5 h-4 w-4 rounded border-input text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    <span>
                        Clear existing queued and pending orders first
                        <span class="block text-xs text-muted-foreground">Required when this user already has open orders. Completed orders are kept.</span>
                    </span>
                </label>
                @error('clearExisting') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror

                <button type="button" wire:click="autoAssign" wire:loading.attr="disabled" wire:target="autoAssign"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 sm:w-auto">
                    <x-dashboard.icon name="sparkles" class="h-4 w-4" wire:loading.remove wire:target="autoAssign" />
                    <span wire:loading.remove wire:target="autoAssign">Auto-assign {{ $totalOrders }} orders</span>
                    <span wire:loading wire:target="autoAssign">Assigning orders...</span>
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-5 py-4">
                <div>
                    <h2 class="font-display text-lg font-bold">Current queue</h2>
                    <p class="text-xs text-muted-foreground">Queued and pending orders for {{ $name }}.</p>
                </div>
                <span class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">{{ $queue->count() }} open</span>
            </div>

            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/50 text-[10px] uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left">Order</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Products</th>
                            <th class="px-4 py-3 text-right">Cost</th>
                            <th class="px-4 py-3 text-right">Commission</th>
                            <th class="px-4 py-3 text-right">Negative</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($queue as $task)
                            @php
                                $queuePosition = $task->available_after_tasks === null
                                    ? null
                                    : max(1, (int) $task->available_after_tasks - $selectedUser->tasks_completed + 1);
                                $queueCommission = $task->profit($normalCommission);
                                $queueNegative = $task->is_merge && $task->merge_negative_balance !== null
                                    ? (int) $task->merge_negative_balance / 100
                                    : null;
                            @endphp
                            <tr wire:key="queue-order-{{ $task->id }}">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-foreground">
                                    {{ $queuePosition !== null ? '#'.$queuePosition : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                        'bg-primary/10 text-primary' => $task->is_merge,
                                        'bg-secondary text-secondary-foreground' => ! $task->is_merge,
                                    ])>{{ $task->is_merge ? 'Merge' : 'Normal' }}</span>
                                </td>
                                <td class="max-w-xs px-4 py-3">
                                    <p class="truncate font-medium text-foreground">{{ $task->title }}</p>
                                    @if ($task->is_merge && ! empty($task->merge_items))
                                        <p class="text-[10px] text-muted-foreground">{{ count($task->merge_items) }} products in this merge</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">${{ number_format((float) $task->cost, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-primary">${{ number_format($queueCommission, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $queueNegative !== null && $queueNegative > 0 ? 'text-destructive' : 'text-muted-foreground' }}">
                                    {{ $queueNegative !== null && $queueNegative > 0 ? '-$'.number_format($queueNegative, 2) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $task->status === 'queued',
                                        'bg-primary/10 text-primary' => $task->status === 'pending',
                                    ])>{{ $task->status }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" wire:click="removeOrder({{ $task->id }})"
                                        wire:confirm="Remove this order from the queue?"
                                        class="inline-flex h-8 items-center justify-center gap-1 rounded-md border border-destructive/30 px-2 text-xs font-medium text-destructive transition-colors hover:bg-destructive/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" /> Remove
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-6 text-center text-sm text-muted-foreground">No queued or pending orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
