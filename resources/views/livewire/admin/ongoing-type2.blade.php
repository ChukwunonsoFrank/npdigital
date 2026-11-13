<div class="space-y-4 animate-fade-in" x-on:toast.self="window.showToast($event.detail.message)">
    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-3">
            @if ($selectedUser)
                <button type="button" wire:click="changeUser" aria-label="Back to users"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
                </button>
            @else
                <a href="{{ route('admin.dashboard.users') }}" aria-label="Back to users"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
                </a>
            @endif
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-bold">Ongoing Type 2</h1>
                <p class="text-sm text-muted-foreground">Assign a Type 2 merge to an order a member is already working on, even after their set was assigned.</p>
            </div>
        </div>

        <a href="{{ route('admin.dashboard.users') }}"
            class="hidden shrink-0 items-center gap-1.5 rounded-md border border-input bg-background px-3 py-2 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:inline-flex">
            <x-dashboard.icon name="users" class="h-3.5 w-3.5" /> Back to users
        </a>
    </div>

    @if (! $selectedUser)
        <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg font-bold">Members with ongoing orders</h2>
                    <p class="text-sm text-muted-foreground">Every member with queued or pending orders. Pick one to review the work in progress.</p>
                </div>
                @if ($users->isNotEmpty())
                    <span class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">{{ $users->count() }} shown</span>
                @endif
            </div>

            <div class="relative mt-4">
                <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input wire:model.live.debounce.300ms="userSearch" type="search" aria-label="Search members"
                    placeholder="Search by name, username, or email..."
                    class="h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-base placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:text-sm" />
            </div>

            <div class="mt-4 divide-y divide-border overflow-hidden rounded-lg border border-border">
                @forelse ($users as $user)
                    <button type="button" wire:key="ongoing-user-{{ $user->id }}" wire:click="selectUser({{ $user->id }})"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                            {{ $user->initials() ?: strtoupper(substr($user->username, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-semibold text-foreground">{{ $user->name ?: $user->username }}</span>
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">{{ ucfirst($user->membership_level ?: 'Normal') }}</span>
                                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">{{ $user->ongoing_orders_count }} ongoing</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-muted-foreground">
                                {{ '@'.$user->username }} &middot; ${{ number_format($user->balance / 100, 2) }} balance
                                @if ($user->negative_balance > 0)
                                    &middot; <span class="font-semibold text-destructive">-${{ number_format($user->negative_balance / 100, 2) }}</span>
                                @endif
                            </span>
                        </span>
                        <x-dashboard.icon name="chevron-right" class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </button>
                @empty
                    <p class="p-6 text-center text-sm text-muted-foreground">No members have queued or pending orders right now.</p>
                @endforelse
            </div>

            @if ($users->count() === 50)
                <p class="mt-3 text-xs text-muted-foreground">Showing the first 50 results. Search to narrow the list.</p>
            @endif
        </div>
    @else
        @php
            $name = $selectedUser->name ?: $selectedUser->username;
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
                        &middot; Set {{ $selectedUser->tasks_completed }} / {{ $selectedUser->productsPerSet() }}
                        &middot; {{ $orders->count() }} ongoing
                    </p>
                </div>
                @if ($selectedUser->negative_balance > 0)
                    <span class="rounded-full bg-destructive/10 px-2.5 py-1 text-xs font-semibold text-destructive">
                        Negative ${{ number_format($selectedUser->negative_balance / 100, 2) }}
                    </span>
                @endif
                <button type="button" wire:click="changeUser"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="users" class="h-3.5 w-3.5" /> Change member
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <div>
                <h2 class="font-display text-xl font-bold">Assign type 2 merge orders</h2>
                <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                    Pick any order the member has not submitted, then choose the products for its merge bundle. The commission and
                    negative balance fill in automatically from the selected products, and you can edit either amount to override it.
                    Product value is the commission divided by this level's type 2 multiplier
                    ({{ rtrim(rtrim(number_format($typeTwoMultiplier, 2, '.', ''), '0'), '.') }}%).
                </p>
            </div>

            @if ($orders->isEmpty())
                <p class="mt-5 rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                    This member has no queued or pending orders to assign.
                </p>
            @else
                <div class="mt-5 space-y-3">
                    @foreach ($orders as $task)
                        @php
                            $selectedItems = $task->is_merge ? [] : ($mergeProducts[$task->id] ?? []);
                            $isPickerOpen = $productPickerTaskId === $task->id;
                            $bundleTotal = array_sum(array_map(fn (array $item): float => (float) ($item['price'] ?? 0), $selectedItems));
                            $commissionInput = $mergeCommission[$task->id] ?? '0.00';
                            $commissionCents = (int) round(((float) $commissionInput) * 100);
                            $productValueCents = (int) round($commissionCents / ($typeTwoMultiplier / 100));
                            $orderPosition = $task->available_after_tasks !== null
                                ? max(1, (int) $task->available_after_tasks - (int) $selectedUser->tasks_completed + 1)
                                : $loop->iteration;
                        @endphp
                        <div wire:key="ongoing-type-two-{{ $task->id }}"
                            class="rounded-xl border {{ $task->is_merge ? 'border-primary/30 bg-primary/[0.03]' : 'border-border bg-muted/20' }} p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="break-words font-display text-base font-bold">
                                        #{{ $orderPosition }} &middot; {{ $task->title }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        Value ${{ number_format((float) $task->cost, 2) }}
                                        &middot; commission ${{ number_format($task->profit($normalCommission), 2) }}
                                        &middot; {{ $task->is_merge ? 'Type 2 order' : 'Normal order' }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $task->status === 'queued',
                                        'bg-primary/10 text-primary' => $task->status === 'pending',
                                    ])>{{ $task->status }}</span>
                                    @if ($task->is_merge)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary">
                                            <x-dashboard.icon name="stack" class="h-3 w-3" /> Type 2 assigned
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if ($task->is_merge)
                                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <div class="rounded-lg border border-border bg-background px-3 py-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Merge commission (USD)</p>
                                        <p class="mt-0.5 font-display text-sm font-bold text-foreground">${{ number_format($task->profit($normalCommission), 2) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-border bg-background px-3 py-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Negative balance (USD)</p>
                                        <p class="mt-0.5 font-display text-sm font-bold {{ (int) $task->merge_negative_balance > 0 ? 'text-destructive' : 'text-foreground' }}">
                                            ${{ number_format((int) $task->merge_negative_balance / 100, 2) }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg border border-border bg-background px-3 py-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Product value</p>
                                        <p class="mt-0.5 font-display text-sm font-bold text-foreground">${{ number_format((float) $task->cost, 2) }}</p>
                                    </div>
                                </div>

                                @if (! empty($task->merge_items))
                                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Combined products</span>
                                        @foreach ($task->merge_items as $item)
                                            <span wire:key="type-two-item-{{ $task->id }}-{{ $item['id'] ?? $loop->index }}"
                                                class="inline-flex max-w-full items-center gap-1 rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">
                                                <span class="truncate">{{ $item['name'] ?? 'Product' }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="mt-4 rounded-xl border border-border bg-background p-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">Bundle</span>
                                        @forelse ($selectedItems as $itemIndex => $item)
                                            <span wire:key="type-two-selected-{{ $task->id }}-{{ $item['id'] ?? $itemIndex }}"
                                                class="inline-flex max-w-full items-center gap-1 rounded-full bg-secondary py-1 pr-1 pl-2 text-[10px] font-semibold text-secondary-foreground">
                                                <span class="truncate">{{ $item['name'] ?? 'Product' }}</span>
                                                <span class="shrink-0 font-bold">${{ number_format((float) ($item['price'] ?? 0), 2) }}</span>
                                                <button type="button" wire:click="removeMergeProduct({{ $task->id }}, {{ $itemIndex }})"
                                                    aria-label="Remove {{ $item['name'] ?? 'product' }} from bundle"
                                                    class="rounded-full p-0.5 text-secondary-foreground/70 transition-colors hover:bg-destructive/10 hover:text-destructive focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <x-dashboard.icon name="x" class="h-3 w-3" />
                                                </button>
                                            </span>
                                        @empty
                                            <span class="text-[10px] font-semibold text-muted-foreground">No products selected yet.</span>
                                        @endforelse

                                        <button type="button" wire:click="toggleProductPicker({{ $task->id }})"
                                            class="ml-auto inline-flex h-8 items-center justify-center gap-1 rounded-md border border-input bg-background px-2.5 text-[10px] font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <x-dashboard.icon :name="$isPickerOpen ? 'x' : 'plus'" class="h-3 w-3" />
                                            {{ $isPickerOpen ? 'Close picker' : 'Add product' }}
                                        </button>
                                    </div>

                                    @error('mergeProducts.'.$task->id)
                                        <p class="mt-2 text-[10px] text-destructive" role="alert">{{ $message }}</p>
                                    @enderror

                                    @if ($isPickerOpen)
                                        <div class="mt-3 rounded-lg border border-border bg-background p-3">
                                            <div class="relative">
                                                <x-dashboard.icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                                <input wire:model.live.debounce.300ms="productSearch" id="type-two-product-search-{{ $task->id }}" type="search"
                                                    aria-label="Search bundle products" placeholder="Search all warehouse products..."
                                                    class="h-9 w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-base placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:text-sm" />
                                            </div>
                                            <p class="mt-1.5 text-[10px] text-muted-foreground">Search across every warehouse level, then add the products this merge should contain.</p>
                                            @error('productSearch') <p class="mt-1.5 text-[10px] text-destructive" role="alert">{{ $message }}</p> @enderror

                                            <div class="mt-2 max-h-64 divide-y divide-border overflow-y-auto rounded-md border border-border">
                                                @forelse ($productResults as $product)
                                                    <button type="button" wire:key="type-two-picker-product-{{ $task->id }}-{{ $product['id'] }}"
                                                        wire:click="addMergeProduct({{ $task->id }}, '{{ $product['id'] }}')"
                                                        class="flex w-full items-center gap-2 px-2.5 py-2 text-left transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted">
                                                            @if (! empty($product['image_url']))
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

                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-foreground">Combined ${{ number_format($bundleTotal, 2) }}</span>
                                        <span class="text-[10px] text-muted-foreground">Commission and negative balance update automatically from these products.</span>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label for="merge-commission-{{ $task->id }}" class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">
                                            Merge commission (USD)
                                        </label>
                                        <input wire:model.live.debounce.400ms="mergeCommission.{{ $task->id }}" id="merge-commission-{{ $task->id }}"
                                            type="number" min="0.01" step="0.01" placeholder="0.00"
                                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm font-semibold text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring @error('mergeCommission.'.$task->id) border-destructive @enderror" />
                                        @error('mergeCommission.'.$task->id)
                                            <p class="text-[10px] text-destructive" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="negative-balance-{{ $task->id }}" class="text-[10px] font-bold uppercase tracking-wide text-muted-foreground">
                                            Negative balance (USD)
                                        </label>
                                        <input wire:model.live.debounce.400ms="negativeBalance.{{ $task->id }}" id="negative-balance-{{ $task->id }}"
                                            type="number" min="0" step="0.01" placeholder="0.00"
                                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm font-semibold text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring @error('negativeBalance.'.$task->id) border-destructive @enderror" />
                                        @error('negativeBalance.'.$task->id)
                                            <p class="text-[10px] text-destructive" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <p class="mt-2 text-[10px] text-muted-foreground">
                                    Auto values are based on the bundle total and the member's wallet balance. Edit either amount to override it.
                                </p>

                                @error('typeTwo.'.$task->id)
                                    <p class="mt-2 text-xs text-destructive" role="alert">{{ $message }}</p>
                                @enderror

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-3">
                                    <p class="text-xs font-semibold text-muted-foreground">
                                        Product value:
                                        <span class="ml-1 font-display text-sm font-bold text-foreground">${{ number_format($productValueCents / 100, 2) }}</span>
                                        <span class="ml-1 font-normal">({{ count($selectedItems) }} selected)</span>
                                    </p>

                                    <button type="button" wire:click="assignTypeTwo({{ $task->id }})"
                                        wire:loading.attr="disabled" wire:target="assignTypeTwo"
                                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                        <x-dashboard.icon name="stack" class="h-4 w-4" wire:loading.remove wire:target="assignTypeTwo" />
                                        <span wire:loading.remove wire:target="assignTypeTwo">Assign as Type 2</span>
                                        <span wire:loading wire:target="assignTypeTwo">Assigning...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
