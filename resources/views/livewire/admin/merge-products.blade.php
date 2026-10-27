<div class="space-y-4" x-on:toast.self="window.showToast($event.detail.message)">
    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" aria-label="Back to overview"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-bold">Merge Products</h1>
                <p class="text-sm text-muted-foreground">Combine products into one task, then choose where it appears in a user's set.</p>
            </div>
        </div>
        <button type="button" wire:click="openModal"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-full bg-[hsl(190,80%,50%)] px-4 text-sm font-medium text-white transition-colors hover:bg-[hsl(190,80%,45%)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:px-6">
            <x-dashboard.icon name="plus" class="h-4 w-4" /> Create
        </button>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Active merges</p>
            <p class="mt-1 font-display text-2xl font-bold">{{ $assignments->count() }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Users assigned</p>
            <p class="mt-1 font-display text-2xl font-bold">{{ $assignments->pluck('user_id')->unique()->count() }}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Outstanding top-ups</p>
            <p class="mt-1 font-display text-2xl font-bold text-destructive">-${{ number_format($assignments->sum(fn ($task) => $task->user?->negative_balance ?? 0) / 100, 2) }}</p>
        </div>
    </div>

    <div class="relative">
        <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <input wire:model.live.debounce.300ms="search" type="search" aria-label="Search users"
            placeholder="Search users with merge products..."
            class="h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-base placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:text-sm" />
    </div>

    <div class="space-y-3 md:hidden">
        @forelse ($assignments as $assignment)
            <div wire:key="merge-mobile-{{ $assignment->id }}" class="rounded-xl border border-border bg-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $assignment->user?->name ?: $assignment->user?->username ?: 'Deleted user' }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ $assignment->user?->username ? '@'.$assignment->user->username : '' }}</p>
                    </div>
                    <span @class([
                        'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                        'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $assignment->status === 'queued',
                        'bg-primary/10 text-primary' => $assignment->status === 'pending',
                    ])>{{ $assignment->status }}</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (($assignment->merge_items ?? []) as $item)
                        <span class="rounded-full bg-secondary px-2 py-1 text-[10px] font-semibold text-secondary-foreground">{{ $item['name'] }}</span>
                    @endforeach
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div><p class="text-[10px] uppercase text-muted-foreground">Cost</p><p class="text-sm font-bold">${{ number_format((float) $assignment->cost, 2) }}</p></div>
                    <div><p class="text-[10px] uppercase text-muted-foreground">Commission</p><p class="text-sm font-bold text-primary">{{ rtrim(rtrim(number_format((float) $assignment->commission_percentage, 2, '.', ''), '0'), '.') }}%</p></div>
                    <div><p class="text-[10px] uppercase text-muted-foreground">Position</p><p class="text-sm font-bold">#{{ $assignment->merge_position ?? max(1, (int) $assignment->available_after_tasks - (int) $assignment->user?->tasks_completed + 1) }}</p></div>
                </div>
                @if (($assignment->user?->negative_balance ?? 0) > 0)
                    <p class="mt-3 text-sm font-bold text-destructive">Negative balance: -${{ number_format($assignment->user->negative_balance / 100, 2) }}</p>
                @endif
                <button type="button" wire:click="removeAssignment({{ $assignment->id }})"
                    wire:confirm="Remove this merge product from {{ $assignment->user?->username }}?"
                    class="mt-3 inline-flex h-9 w-full items-center justify-center gap-1 rounded-md border border-destructive/30 text-xs font-medium text-destructive hover:bg-destructive/5">
                    <x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" /> Remove merge
                </button>
            </div>
        @empty
            <p class="rounded-xl border border-border bg-card p-6 text-center text-sm text-muted-foreground">No merge products are assigned yet.</p>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-xl border border-border bg-card md:block">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Products</th>
                    <th class="px-4 py-3 text-right">Combined cost</th>
                    <th class="px-4 py-3 text-right">Commission</th>
                    <th class="px-4 py-3 text-center">Position</th>
                    <th class="px-4 py-3 text-right">Negative</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr wire:key="merge-desktop-{{ $assignment->id }}" class="border-t border-border/60 align-top">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $assignment->user?->name ?: $assignment->user?->username ?: 'Deleted user' }}</p>
                            <p class="text-xs text-muted-foreground">{{ $assignment->user?->username ? '@'.$assignment->user->username : '' }} · {{ ucfirst((string) $assignment->user?->membership_level) }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex max-w-xs flex-col gap-1">
                                @foreach (($assignment->merge_items ?? []) as $item)
                                    <span class="flex items-center justify-between gap-2 rounded-md bg-muted/60 px-2 py-1 text-xs">
                                        <span class="truncate">{{ $item['name'] }}</span>
                                        <span class="shrink-0 font-semibold">${{ number_format((float) $item['price'], 2) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold">${{ number_format((float) $assignment->cost, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-primary">{{ rtrim(rtrim(number_format((float) $assignment->commission_percentage, 2, '.', ''), '0'), '.') }}%</td>
                        <td class="px-4 py-3 text-center font-semibold">#{{ $assignment->merge_position ?? max(1, (int) $assignment->available_after_tasks - (int) $assignment->user?->tasks_completed + 1) }}</td>
                        <td class="px-4 py-3 text-right font-bold {{ ($assignment->user?->negative_balance ?? 0) > 0 ? 'text-destructive' : 'text-muted-foreground' }}">
                            {{ ($assignment->user?->negative_balance ?? 0) > 0 ? '-$'.number_format($assignment->user->negative_balance / 100, 2) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $assignment->status === 'queued',
                                'bg-primary/10 text-primary' => $assignment->status === 'pending',
                            ])>{{ $assignment->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="removeAssignment({{ $assignment->id }})"
                                wire:confirm="Remove this merge product from {{ $assignment->user?->username }}?"
                                class="inline-flex h-8 items-center justify-center gap-1 rounded-md border border-destructive/30 px-2 text-xs font-medium text-destructive hover:bg-destructive/5">
                                <x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" /> Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-6 text-center text-muted-foreground">No merge products are assigned yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showModal)
        @teleport('body')
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/70 p-4"
            wire:click.self="closeModal" x-on:keydown.escape.window="$wire.closeModal()">
            <section role="dialog" aria-modal="true" aria-labelledby="merge-title" x-trap.inert.noscroll="true"
                class="relative max-h-[92vh] w-full max-w-3xl overflow-hidden rounded-xl border border-border bg-card text-foreground shadow-xl">
                <button type="button" wire:click="closeModal" aria-label="Close merge builder"
                    class="absolute right-4 top-4 z-10 rounded-sm p-1 text-muted-foreground hover:text-foreground">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <form wire:submit="save" class="flex max-h-[92vh] flex-col">
                    <div class="shrink-0 border-b border-border px-6 py-4">
                        <h2 id="merge-title" class="pr-8 font-display text-lg font-bold">Create Merge Product</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">Pick any products from across the warehouse and set their combined price.</p>
                    </div>

                    <div class="min-h-0 space-y-5 overflow-y-auto px-6 py-5">
                        <section class="space-y-3">
                            <h3 class="text-sm font-bold">1. Choose a user</h3>

                            @if ($selectedUser)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-muted/20 p-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $selectedUser->name ?: $selectedUser->username }}</p>
                                        <p class="text-xs text-muted-foreground">{{ '@'.$selectedUser->username }} · {{ ucfirst($selectedUser->membership_level) }} · Set {{ $selectedUser->tasks_completed }}/{{ $selectedUser->productsPerSet() }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-xs text-muted-foreground">Balance</p>
                                        <p class="text-sm font-bold">${{ number_format($selectedUser->balance / 100, 2) }}</p>
                                    </div>
                                    <button type="button" wire:click="$set('selectedUserId', null)"
                                        class="shrink-0 rounded-md border border-input px-2 py-1 text-xs hover:bg-muted">Change</button>
                                </div>
                            @else
                                <flux:input wire:model.live.debounce.300ms="userSearch" icon="magnifying-glass" placeholder="Search by name or username" />
                                <div class="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-border p-2" aria-label="Available users">
                                    @forelse ($users as $user)
                                        <button type="button" wire:key="merge-user-{{ $user->id }}" wire:click="selectUser({{ $user->id }})"
                                            class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left hover:bg-muted">
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm">{{ $user->name ?: $user->username }}</span>
                                                <span class="block text-xs text-muted-foreground">{{ '@'.$user->username }} · {{ ucfirst($user->membership_level) }}</span>
                                            </span>
                                            <span class="shrink-0 text-xs font-semibold">${{ number_format($user->balance / 100, 2) }}</span>
                                        </button>
                                    @empty
                                        <p class="p-3 text-sm text-muted-foreground">No users found.</p>
                                    @endforelse
                                </div>
                            @endif
                            @error('selectedUserId') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                        </section>

                        @if ($selectedUser)
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <flux:input wire:model="position" label="Position in set" type="number" min="1"
                                        max="{{ max(1, $selectedUser->productsPerSet() - $selectedUser->tasks_completed) }}" />
                                    <p class="mt-1 text-xs text-muted-foreground">Appears as product #{{ $position }} of the set. {{ max(0, $selectedUser->productsPerSet() - $selectedUser->tasks_completed) }} remaining.</p>
                                    @error('position') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div class="rounded-lg border border-amber-300 bg-amber-500/5 p-3 text-xs text-muted-foreground">
                                    <p class="font-semibold text-foreground">Merge requirement</p>
                                    <p class="mt-1">Combined cost must exceed the user's balance, so they top up before submitting. Commission is fixed at 5%.</p>
                                </div>
                            </div>

                            <section class="space-y-3">
                                <div class="flex flex-wrap items-end justify-between gap-2">
                                    <h3 class="text-sm font-bold">2. Add products</h3>
                                    <div class="w-full sm:w-48">
                                        <flux:select wire:model.live="productLevel" aria-label="Product level">
                                            <option value="">All levels</option>
                                            @foreach ($levels as $level)
                                                <option value="{{ $level['value'] }}">{{ $level['label'] }}</option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                </div>
                                <flux:input wire:model.live.debounce.300ms="productSearch" icon="magnifying-glass" placeholder="Search all warehouse products" />
                                @error('productSearch') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror

                                <div class="grid max-h-56 grid-cols-1 gap-1 overflow-y-auto rounded-xl border border-border p-2 sm:grid-cols-2">
                                    @forelse ($productResults as $product)
                                        @php $alreadySelected = collect($selectedProducts)->contains('id', $product['id']); @endphp
                                        <button type="button" wire:key="merge-product-option-{{ $product['id'] }}"
                                            wire:click="addProduct('{{ $product['id'] }}')" @disabled($alreadySelected)
                                            class="flex items-center gap-2 rounded-md px-2 py-2 text-left hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted">
                                                @if ($product['image_url'])
                                                    <img src="{{ $product['image_url'] }}" alt="" class="h-full w-full object-cover" />
                                                @else
                                                    <x-dashboard.icon name="package" class="h-4 w-4 text-muted-foreground" />
                                                @endif
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-xs font-semibold">{{ $product['name'] }}</span>
                                                <span class="block text-[10px] text-muted-foreground">{{ ucfirst($product['level']) }} · Day {{ $product['day'] }} {{ strtoupper($product['group']) }}</span>
                                            </span>
                                            <span class="shrink-0 text-xs font-bold">${{ number_format((float) $product['price'], 2) }}</span>
                                            <x-dashboard.icon :name="$alreadySelected ? 'check' : 'plus'" class="h-4 w-4 shrink-0 text-primary" />
                                        </button>
                                    @empty
                                        <p class="col-span-full p-3 text-sm text-muted-foreground">No products match your search.</p>
                                    @endforelse
                                </div>
                            </section>

                            <section class="space-y-3">
                                <h3 class="text-sm font-bold">3. Set prices</h3>
                                @forelse ($selectedProducts as $index => $product)
                                    <div wire:key="selected-merge-product-{{ $product['id'] }}" class="flex items-center gap-3 rounded-xl border border-border p-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted">
                                            @if ($product['image_url'])
                                                <img src="{{ $product['image_url'] }}" alt="" class="h-full w-full object-cover" />
                                            @else
                                                <x-dashboard.icon name="package" class="h-4 w-4 text-muted-foreground" />
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold">{{ $product['name'] }}</p>
                                            <p class="text-[10px] text-muted-foreground">{{ ucfirst($product['level']) }} · Day {{ $product['day'] }} {{ strtoupper($product['group']) }}</p>
                                        </div>
                                        <div class="w-28 shrink-0">
                                            <flux:input wire:model.live.debounce.300ms="selectedProducts.{{ $index }}.price" type="number" min="0.01" step="0.01" aria-label="{{ $product['name'] }} price" />
                                            @error('selectedProducts.'.$index.'.price') <p class="mt-1 text-[10px] text-destructive" role="alert">{{ $message }}</p> @enderror
                                        </div>
                                        <button type="button" wire:click="removeProduct({{ $index }})" aria-label="Remove {{ $product['name'] }}"
                                            class="shrink-0 rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive">
                                            <x-dashboard.icon name="x" class="h-4 w-4" />
                                        </button>
                                    </div>
                                @empty
                                    <p class="rounded-xl border border-dashed border-border p-4 text-center text-sm text-muted-foreground">No products selected yet.</p>
                                @endforelse
                                @error('selectedProducts') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                            </section>

                            @php
                                $combined = collect($selectedProducts)->sum(fn ($product) => is_numeric($product['price']) ? (float) $product['price'] : 0);
                                $profit = $combined * $commissionPercentage / 100;
                            @endphp
                            <div class="grid grid-cols-3 gap-2 rounded-xl border border-primary/30 bg-primary/5 p-3 text-center">
                                <div><p class="text-[10px] uppercase text-muted-foreground">Combined cost</p><p class="font-display text-lg font-bold">${{ number_format($combined, 2) }}</p></div>
                                <div><p class="text-[10px] uppercase text-muted-foreground">Commission</p><p class="font-display text-lg font-bold text-primary">5%</p></div>
                                <div><p class="text-[10px] uppercase text-muted-foreground">User earns</p><p class="font-display text-lg font-bold text-emerald-600">${{ number_format($profit, 2) }}</p></div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div role="alert" class="space-y-1 text-sm text-destructive">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="grid shrink-0 grid-cols-2 gap-2 border-t border-border px-6 py-4">
                        <button type="button" wire:click="closeModal" class="h-10 rounded-lg border border-input bg-background text-sm hover:bg-muted">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="h-10 rounded-lg bg-[hsl(190,100%,44%)] text-sm font-medium text-white hover:bg-[hsl(190,100%,39%)] disabled:opacity-50">
                            Assign Merge Product
                        </button>
                    </div>
                </form>
            </section>
        </div>
        @endteleport
    @endif
</div>
