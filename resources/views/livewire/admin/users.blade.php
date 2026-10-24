<div>
    @unless ($taskSummary)
    <div class="space-y-4 animate-fade-in">
        <h1 class="font-display text-2xl font-bold text-foreground">User Management</h1>

        <div class="relative">
            <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
            <input wire:model.live.debounce.300ms="query" type="search" placeholder="Search users..."
                class="h-10 w-full rounded-md border-border bg-card py-2 pl-10 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus-visible:ring-ring focus:ring-offset-2" />
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card divide-y divide-border">
        @forelse ($users as $user)
            @php
                $name = $user->name ?: $user->username;
                $membership = strtoupper($user->membership_level ?: 'Normal');
                $avatarColor = match (strtolower($membership)) {
                    'gold' => 'bg-amber-500',
                    'diamond' => 'bg-sky-400',
                    'platinum' => 'bg-slate-500',
                    default => 'bg-primary',
                };
                $membershipColor = match (strtolower($membership)) {
                    'gold' => 'bg-amber-500 text-white',
                    'diamond' => 'bg-sky-400 text-white',
                    'platinum' => 'bg-slate-500 text-white',
                    default => 'bg-muted text-muted-foreground',
                };
            @endphp
            <button type="button" wire:click="showUserDetails({{ $user->id }})" wire:key="user-{{ $user->id }}"
                class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-bold text-white">
                    {{ $user->initials() ?: strtoupper(substr($name, 0, 1)) }}
                </div>
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <p class="truncate text-sm font-semibold text-foreground">{{ $name }}</p>
                    <span class="shrink-0 rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">{{ $user->is_training ? 'VIRTUAL' : 'REAL' }}</span>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $membershipColor }}">{{ $membership }}</span>
            </button>
        @empty
            <div class="p-12 text-center text-sm text-muted-foreground">No users found.</div>
        @endforelse
        </div>

        @if ($users->hasPages())
            <div>{{ $users->links() }}</div>
        @endif
    </div>
    @endunless

    @if ($taskSummary && $selectedUser)
        @include('livewire.admin.partials.task-user-summary')
    @endif

    @if ($selectedUser && ! $taskSummary)
        @php
            $name = $selectedUser->name ?: $selectedUser->username;
            $membership = strtoupper($selectedUser->membership_level ?: 'Normal');
            $membershipColor = match (strtolower($membership)) {
                'gold' => 'bg-amber-500 text-white',
                'diamond' => 'bg-sky-400 text-white',
                'platinum' => 'bg-slate-500 text-white',
                default => 'bg-muted text-muted-foreground',
            };
            $onHoldBalance = $selectedUser->lien_status === 'on_hold' ? $selectedUser->lien_amount : 0;
        @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeUserDetails">
            <section role="dialog" aria-modal="true" aria-labelledby="user-details-title" class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeUserDetails" aria-label="Close user details"
                    class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground transition-opacity hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <header class="space-y-1.5 text-center sm:text-left">
                    <h2 id="user-details-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">User Details</h2>
                    <p class="text-sm text-muted-foreground">Viewing profile for {{ $name }}</p>
                </header>

                <div class="mt-5 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary text-lg font-bold text-white">{{ $selectedUser->initials() ?: strtoupper(substr($name, 0, 1)) }}</div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-lg font-bold text-foreground">{{ $name }}</p>
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">{{ $selectedUser->is_training ? 'Virtual Account' : 'Real Account' }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ $selectedUser->mobile_number ?: 'No phone number' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 rounded-lg border border-primary/10 bg-primary/5 p-3 text-sm">
                        <x-dashboard.icon name="map-pin" class="h-4 w-4 shrink-0 text-primary" />
                        <div>
                            <p class="text-xs text-muted-foreground">Current Location</p>
                            <p class="font-medium text-foreground">{{ $selectedUser->timezone ?: 'Not available' }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="flex items-center gap-1 text-xs text-muted-foreground"><x-dashboard.icon name="mail" class="h-3 w-3" /> Email</p>
                            <p class="font-medium text-foreground">{{ $selectedUser->email ?: 'Not available' }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Phone</p><p class="font-medium text-foreground">{{ $selectedUser->mobile_number ?: 'Not available' }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Invitation Code</p><p class="font-mono font-medium text-foreground">{{ $selectedUser->referral_code ?: 'Not available' }}</p></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Membership Level</p><span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $membershipColor }}">{{ $membership }}</span></div>
                            <div><p class="text-xs text-muted-foreground">Account Type</p><p class="font-medium text-foreground">{{ $selectedUser->is_training ? 'Virtual' : 'Real' }}</p></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Wallet Balance</p><p class="font-bold text-foreground">${{ number_format($selectedUser->balance / 100, 2) }}</p></div>
                            <div><p class="text-xs text-muted-foreground">On Hold Balance</p><p class="font-bold text-foreground">${{ number_format($onHoldBalance / 100, 2) }}</p></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Status</p><span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $selectedUser->account_status === 'suspended' ? 'bg-destructive/10 text-destructive' : 'bg-secondary text-secondary-foreground' }}">{{ ucfirst($selectedUser->account_status) }}</span></div>
                            <div><p class="text-xs text-muted-foreground">Total Earned</p><p class="font-bold text-foreground">${{ number_format($selectedUser->total_commission / 100, 2) }}</p></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Joined</p><p class="font-medium text-foreground">{{ $selectedUser->created_at?->format('n/j/Y') ?: 'Not available' }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Team Size</p><p class="font-bold text-foreground">0</p></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Total Submitted</p><p class="font-bold text-primary">{{ $selectedUser->tasks_completed }} / {{ $selectedUser->productsPerSet() }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Tasks Available</p><p class="font-bold text-foreground">{{ max($selectedUser->productsPerSet() - $selectedUser->tasks_completed, 0) }} / {{ $selectedUser->productsPerSet() }}</p></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-xs text-muted-foreground">Exclusive Tasks</p><p class="font-bold text-primary">{{ $exclusiveTaskCount }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Upgrade Checkpoint</p><p class="font-medium text-foreground">{{ $selectedUser->required_upgrade_level ?: '—' }}</p></div>
                        </div>

                        <div><p class="text-xs text-muted-foreground">Product Day Group</p><p class="font-bold text-foreground">{{ $selectedUser->warehouseGroup() ? 'Day '.$selectedUser->warehouseGroup()['day'].' · Group '.strtoupper($selectedUser->warehouseGroup()['group']) : 'Not assigned' }}</p></div>
                        <div><p class="flex items-center gap-1 text-xs text-muted-foreground"><x-dashboard.icon name="key-round" class="h-3 w-3" /> Transaction Password</p><p class="font-mono font-bold text-foreground">••••••••</p></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 sm:grid-cols-3">
                        <button type="button" wire:click="editUser" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="pencil" class="mr-1 h-3 w-3" /> Edit</button>
                        <button type="button" wire:click="openAddExclusive" class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="plus" class="h-3.5 w-3.5" /> Add Exclusive</button>
                        <button type="button" wire:click="openSetProducts" class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="list-todo" class="h-3.5 w-3.5" /> Set Products</button>
                        <button type="button" class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="gift" class="h-3.5 w-3.5" /> Reward Event</button>
                        <button type="button" wire:click="openUpgradeTarget" class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="circle-arrow-up" class="h-3.5 w-3.5" /> Set Upgrade</button>
                        <button type="button" disabled class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="x" class="h-3.5 w-3.5" /> Clear Negative</button>
                        <button type="button" wire:click="clearRecord" wire:confirm="CLEAR RECORD: This will permanently reset the submission counter to 0, delete ALL submission records, and clear the on-hold balance. This action is IRREVERSIBLE. Continue?" class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="file-text" class="h-3.5 w-3.5" /> Clear Record</button>
                        <button type="button" wire:click="openFunds" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="dollar-sign" class="mr-1 h-3 w-3" /> Funds</button>
                        <button type="button" wire:click="viewAsUser" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-emerald-500/30 bg-background px-3 text-xs font-medium text-emerald-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="log-in" class="mr-1 h-3 w-3" /> View as User</button>
                        @if ($selectedUser->account_status === 'suspended')
                            <button type="button" wire:click="unsuspendUser" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-emerald-500/30 bg-background px-3 text-xs font-medium text-emerald-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="circle-check" class="mr-1 h-3 w-3" /> Unsuspend</button>
                        @else
                            <button type="button" wire:click="suspendUser" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-destructive/30 bg-background px-3 text-xs font-medium text-destructive transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="x" class="mr-1 h-3 w-3" /> Suspend</button>
                        @endif
                        <button type="button" wire:click="deleteUser" wire:confirm="DELETE ACCOUNT: This will permanently delete {{ $name }}'s account and ALL related records. This action is IRREVERSIBLE. Continue?" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-destructive/30 bg-background px-3 text-xs font-medium text-destructive transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="trash-2" class="mr-1 h-3 w-3" /> Delete Account</button>
                        <button type="button" wire:click="openUpgradeMembership" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-primary/30 bg-background px-3 text-xs font-medium text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="circle-arrow-up" class="mr-1 h-3 w-3" /> Upgrade</button>
                        <button type="button" wire:click="openDowngradeMembership" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-destructive/30 bg-background px-3 text-xs font-medium text-destructive transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"><x-dashboard.icon name="circle-arrow-down" class="mr-1 h-3 w-3" /> Downgrade</button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($showSetProducts && $selectedUser)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeSetProducts" x-on:keydown.escape.window="$wire.closeSetProducts()">
            <section role="dialog" aria-modal="true" aria-labelledby="set-products-title" class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeSetProducts" aria-label="Close warehouse group assignment" class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>
                <h2 id="set-products-title" class="pr-5 text-center text-xl font-bold text-foreground">Assign warehouse group</h2>
                <p class="mt-4 text-center text-sm text-muted-foreground">{{ $selectedUser->membership_level }} level. Only products from the assigned group will appear. Groups the user has fully submitted are disabled.</p>
                <p class="mt-2 text-center text-xs text-muted-foreground">Any current task and assigned exclusives will still be available.</p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    @foreach ($warehouseGroups as $warehouseGroup)
                        @php
                            $isSelected = $selectedUser->warehouseGroup() === ['day' => $warehouseGroup['day'], 'group' => $warehouseGroup['group']];
                        @endphp
                        <button type="button" wire:key="warehouse-group-{{ $warehouseGroup['day'] }}-{{ $warehouseGroup['group'] }}" wire:click="assignWarehouseGroup({{ $warehouseGroup['day'] }}, '{{ $warehouseGroup['group'] }}')" wire:loading.attr="disabled" @disabled($warehouseGroup['remaining'] === 0) aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                            @class(['rounded-xl border p-3 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50', 'border-primary bg-primary/10' => $isSelected, 'border-border hover:bg-muted/50' => ! $isSelected])>
                            <span class="block text-sm font-bold text-foreground">Day {{ $warehouseGroup['day'] }} · Group {{ strtoupper($warehouseGroup['group']) }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $warehouseGroup['count'] }} products</span>
                            @if ($warehouseGroup['remaining'] === 0)
                                <span class="block text-xs text-muted-foreground">{{ $warehouseGroup['count'] === 0 ? 'Empty group' : 'Fully submitted' }}</span>
                            @elseif ($warehouseGroup['remaining'] < $warehouseGroup['count'])
                                <span class="block text-xs text-muted-foreground">{{ $warehouseGroup['remaining'] }} remaining</span>
                            @endif
                        </button>
                    @endforeach
                </div>
                @if ($warehouseGroups === [])
                    <p class="mt-4 text-sm text-muted-foreground">No warehouse groups available for this level.</p>
                @endif
                @error('warehouseGroup') <p class="mt-3 text-sm text-destructive" role="alert">{{ $message }}</p> @enderror
                <flux:button type="button" wire:click="closeSetProducts" class="mt-4 w-full">Close</flux:button>
            </section>
        </div>
    @endif

    @if ($showAddExclusive && $selectedUser)
        @php
            $exclusiveProduct = collect($exclusiveProducts)->firstWhere('id', $exclusiveProductId);
        @endphp
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeAddExclusive" x-on:keydown.escape.window="$wire.closeAddExclusive()">
            <section role="dialog" aria-modal="true" aria-labelledby="add-exclusive-title" class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeAddExclusive" aria-label="Close add exclusive" class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground hover:text-foreground">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>
                <h2 id="add-exclusive-title" class="text-lg font-semibold text-foreground">{{ $taskSummary ? 'Add Premium Task' : 'Add Exclusive Task' }}</h2>
                <form wire:submit="assignExclusive" class="mt-6 space-y-4">
                    <p class="text-sm text-muted-foreground">Adding {{ $taskSummary ? 'premium' : 'exclusive' }} task for <strong class="text-foreground">{{ $selectedUser->name ?: $selectedUser->username }}</strong></p>
                    <div class="flex items-center justify-between rounded-lg border border-border bg-muted/30 p-3 text-sm">
                        <span class="text-muted-foreground">Current Balance</span>
                        <strong class="text-emerald-600">${{ number_format($selectedUser->balance / 100, 2) }}</strong>
                    </div>
                    <div class="space-y-2">
                        <label for="exclusive-product" class="text-sm font-medium text-foreground">{{ $taskSummary ? 'Premium' : 'Exclusive' }} Product ({{ $selectedUser->membership_level }})</label>
                        <flux:select id="exclusive-product" wire:model.live="exclusiveProductId" placeholder="Select product">
                            @foreach ($exclusiveProducts as $product)
                                <option value="{{ $product['id'] }}" wire:key="exclusive-option-{{ $product['id'] }}">{{ $product['name'] }}{{ $product['name2'] ? ' + '.$product['name2'] : '' }}</option>
                            @endforeach
                        </flux:select>
                        @error('exclusiveProductId') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @if ($exclusiveProducts === [])
                        <p class="text-sm text-muted-foreground">No exclusive products available for this level.</p>
                    @endif
                    @if ($exclusiveProduct)
                        <div wire:key="exclusive-preview-{{ $exclusiveProductId }}" class="space-y-4 rounded-lg border border-amber-300 bg-amber-500/5 p-4">
                            <span class="inline-flex rounded-full bg-amber-500 px-3 py-1 text-xs font-bold text-white">EXCLUSIVE</span>
                            <div class="flex items-center gap-3">
                                @if ($exclusiveProduct['image_url'])
                                    <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-md">
                                        <img src="{{ $exclusiveProduct['image_url'] }}" alt="{{ $exclusiveProduct['name'] }}" class="h-full w-full object-cover">
                                        <x-product-brand-logo :brand="$exclusiveProduct['brand']" size="sm" />
                                    </div>
                                @endif
                                <p class="text-sm font-bold text-foreground">{{ $exclusiveProduct['name'] }}{{ $exclusiveProduct['name2'] ? ' + '.$exclusiveProduct['name2'] : '' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 border-t border-amber-300 pt-4">
                                <div class="space-y-2">
                                    <label for="exclusive-price" class="text-sm font-medium text-foreground">Price ($)</label>
                                    <flux:input id="exclusive-price" wire:model.live.debounce.300ms="exclusivePrice" type="number" min="0.01" step="0.01" />
                                    @error('exclusivePrice') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="exclusive-commission" class="text-sm font-medium text-foreground">Commission (%)</label>
                                    <flux:input id="exclusive-commission" wire:model.live.debounce.300ms="exclusiveCommission" type="number" min="0" max="100" step="0.01" />
                                    @error('exclusiveCommission') <p class="text-xs text-destructive" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <p class="text-sm font-semibold text-emerald-600">Cost: ${{ number_format(is_numeric($exclusivePrice) ? (float) $exclusivePrice : 0, 2) }} · Profit: ${{ number_format(is_numeric($exclusivePrice) && is_numeric($exclusiveCommission) ? (float) $exclusivePrice * (float) $exclusiveCommission / 100 : 0, 2) }}</p>
                        </div>
                    @endif
                    <p class="text-xs text-muted-foreground">This exclusive will appear once, after the current task. If no task is in progress, it will be next.</p>
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <flux:button type="button" wire:click="closeAddExclusive">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" class="bg-emerald-600! hover:bg-emerald-700! disabled:opacity-50" wire:loading.attr="disabled" wire:target="assignExclusive" :disabled="! $exclusiveProduct">{{ $taskSummary ? 'Add Premium' : 'Add Exclusive' }}</flux:button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($showUpgradeMembership && $selectedUser)
        @php
            $upgradeName = $selectedUser->name ?: $selectedUser->username;
            $upgradeCurrentLevel = $selectedUser->membership_level ?: 'Normal';
        @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeUpgradeMembership">
            <section role="dialog" aria-modal="true" aria-labelledby="upgrade-membership-title"
                class="relative w-full max-w-sm rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeUpgradeMembership" aria-label="Close upgrade membership"
                    class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground transition-opacity hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <header class="flex flex-col space-y-1.5 text-center sm:text-left">
                    <h2 id="upgrade-membership-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">Upgrade Membership</h2>
                    <p class="text-sm text-muted-foreground">{{ $upgradeName }} — Current: <span class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">{{ $upgradeCurrentLevel }}</span></p>
                </header>

                <div class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <label for="upgrade-membership-level" class="text-sm font-medium leading-none text-foreground">New Membership Level</label>
                        <select wire:model="upgradeMembershipLevel" id="upgrade-membership-level"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                            @foreach ($membershipLevels as $membershipLevel)
                                @php
                                    $isCurrentLevel = $membershipLevel->name === $upgradeCurrentLevel;
                                @endphp
                                <option value="{{ $membershipLevel->name }}" @disabled($isCurrentLevel)>{{ $membershipLevel->name }}{{ $isCurrentLevel ? ' (current)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('upgradeMembershipLevel')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeUpgradeMembership"
                        class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        Cancel
                    </button>
                    <button type="button" wire:click="applyUpgradeMembership" wire:loading.attr="disabled"
                        class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                        <x-dashboard.icon name="circle-arrow-up" class="mr-1 h-3 w-3" />
                        Upgrade
                    </button>
                </div>
            </section>
        </div>
    @endif

    @if ($showDowngradeMembership && $selectedUser)
        @php
            $downgradeName = $selectedUser->name ?: $selectedUser->username;
            $downgradeCurrentLevel = $selectedUser->membership_level ?: 'Normal';
        @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeDowngradeMembership">
            <section role="dialog" aria-modal="true" aria-labelledby="downgrade-membership-title"
                class="relative w-full max-w-sm rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeDowngradeMembership" aria-label="Close downgrade membership"
                    class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground transition-opacity hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <header class="flex flex-col space-y-1.5 text-center sm:text-left">
                    <h2 id="downgrade-membership-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">Downgrade Membership</h2>
                    <p class="text-sm text-muted-foreground">{{ $downgradeName }} — Current: <span class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">{{ $downgradeCurrentLevel }}</span></p>
                </header>

                <div class="mt-4 space-y-4">
                    @if ($downgradeMembershipLevels->isEmpty())
                        <p class="text-sm text-muted-foreground">This user is already on the lowest membership level.</p>
                    @else
                        <div class="space-y-2">
                            <label for="downgrade-membership-level" class="text-sm font-medium leading-none text-foreground">New Membership Level</label>
                            <select wire:model="downgradeMembershipLevel" id="downgrade-membership-level"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                                @foreach ($downgradeMembershipLevels as $membershipLevel)
                                    <option value="{{ $membershipLevel->name }}">{{ $membershipLevel->name }}</option>
                                @endforeach
                            </select>
                            @error('downgradeMembershipLevel')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeDowngradeMembership"
                        class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        Cancel
                    </button>
                    @if ($downgradeMembershipLevels->isNotEmpty())
                        <button type="button" wire:click="applyDowngradeMembership" wire:loading.attr="disabled"
                            class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md bg-destructive px-3 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            <x-dashboard.icon name="circle-arrow-down" class="mr-1 h-3 w-3" />
                            Downgrade
                        </button>
                    @endif
                </div>
            </section>
        </div>
    @endif

    @if ($editingUserId)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeEditUser">
            <section role="dialog" aria-modal="true" aria-labelledby="edit-user-title" class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeEditUser" aria-label="Close edit user"
                    class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground transition-opacity hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <header class="space-y-1.5 text-center sm:text-left">
                    <h2 id="edit-user-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">Edit User</h2>
                    <p class="text-sm text-muted-foreground">Editing {{ $editName ?: 'this user' }}'s account details</p>
                </header>

                <form wire:submit="saveUser" class="mt-5 space-y-4">
                    <div class="space-y-2">
                        <label for="edit-name" class="text-sm font-medium leading-none text-foreground">Full Name</label>
                        <input wire:model="editName" id="edit-name" type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                        @error('editName')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="edit-username" class="text-sm font-medium leading-none text-foreground">Username</label>
                        <input wire:model="editUsername" id="edit-username" type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                        @error('editUsername')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="edit-email" class="text-sm font-medium leading-none text-foreground">Email</label>
                        <input wire:model="editEmail" id="edit-email" type="email"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                        @error('editEmail')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="edit-phone" class="text-sm font-medium leading-none text-foreground">Phone Number</label>
                        <input wire:model="editMobileNumber" id="edit-phone" type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                        @error('editMobileNumber')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="edit-password" class="text-sm font-medium leading-none text-foreground">New Password <span class="text-xs font-normal text-muted-foreground">(leave blank to keep current)</span></label>
                        <div class="relative" x-data="{ visible: false }">
                            <input wire:model="editPassword" id="edit-password" type="password" placeholder="Enter new password"
                                x-bind:type="visible ? 'text' : 'password'"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 pr-10 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                            <button type="button" x-on:click="visible = ! visible" aria-label="Toggle new password visibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground focus-visible:outline-none">
                                <x-dashboard.icon name="eye" class="h-4 w-4" x-show="! visible" x-cloak />
                                <x-dashboard.icon name="eye-off" class="h-4 w-4" x-show="visible" x-cloak />
                            </button>
                        </div>
                        @error('editPassword')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="edit-txpass" class="text-sm font-medium leading-none text-foreground">Transaction Password</label>
                        <div class="relative" x-data="{ visible: false }">
                            <input wire:model="editTransactionPassword" id="edit-txpass" type="password"
                                x-bind:type="visible ? 'text' : 'password'"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 pr-10 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                            <button type="button" x-on:click="visible = ! visible" aria-label="Toggle transaction password visibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground focus-visible:outline-none">
                                <x-dashboard.icon name="eye" class="h-4 w-4" x-show="! visible" x-cloak />
                                <x-dashboard.icon name="eye-off" class="h-4 w-4" x-show="visible" x-cloak />
                            </button>
                        </div>
                        @error('editTransactionPassword')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="edit-credit" class="text-sm font-medium leading-none text-foreground">Credit Score</label>
                        <input wire:model="editCreditScore" id="edit-credit" type="number" min="0" max="100"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30" />
                        @error('editCreditScore')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeEditUser"
                            class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            <x-dashboard.icon name="save" class="h-3 w-3" />
                            Save Changes
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($upgradeTargetUser)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeUpgradeTarget">
            <section role="dialog" aria-modal="true" aria-labelledby="set-upgrade-target-title"
                class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeUpgradeTarget" aria-label="Close set upgrade target"
                    class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground transition-opacity hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <header class="space-y-1.5 text-center sm:text-left">
                    <h2 id="set-upgrade-target-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">Set Upgrade Target</h2>
                </header>

                <div class="mt-3 space-y-3">
                    <p class="text-sm text-muted-foreground">
                        Set a required upgrade for
                        <span class="font-bold text-foreground">{{ $upgradeTargetUser->name ?: $upgradeTargetUser->username }}</span>
                        (currently {{ $upgradeTargetUser->membership_level ?: 'Normal' }}). The user will not be able to submit tasks until they upgrade.
                    </p>

                    <div class="space-y-2">
                        <label for="upgrade-target-level" class="text-xs font-medium leading-none text-foreground">Target Level</label>
                        <select wire:model="upgradeTargetLevel" id="upgrade-target-level"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                            <option value="">Select target level</option>
                            @foreach ($upgradeTargetLevels as $membershipLevel)
                                <option value="{{ $membershipLevel->name }}">{{ $membershipLevel->name }}</option>
                            @endforeach
                        </select>
                        @error('upgradeTargetLevel')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeUpgradeTarget"
                        class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        Cancel
                    </button>
                    <button type="button" wire:click="setUpgradeTarget" wire:loading.attr="disabled"
                        class="inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                        Set Target
                    </button>
                </div>
            </section>
        </div>
    @endif

    @if ($manageFundsUser)
        @php
            $fundsName = $manageFundsUser->name ?: $manageFundsUser->username;
            $fundsOnHold = $manageFundsUser->lien_status === 'on_hold' ? (int) $manageFundsUser->lien_amount : 0;
            $fundsAmountValue = number_format((float) $fundsAmount, 2);
            $fundsButtonBase = 'inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md px-3 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-3 [&_svg]:shrink-0';
            $fundsButtonIdle = 'border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground';
            $fundsActionStyles = [
                'add_funds' => 'bg-primary text-primary-foreground hover:bg-primary/90',
                'deduct_funds' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                'return_balance' => 'bg-emerald-600 text-white hover:bg-emerald-700',
                'add_todays_earnings' => 'bg-emerald-500 text-white hover:bg-emerald-600',
                'deduct_todays_earnings' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                'add_on_hold' => 'bg-primary text-primary-foreground hover:bg-primary/90',
                'deduct_on_hold' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
            ];
            $fundsConfirmStyles = [
                'deduct_funds' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                'return_balance' => 'bg-emerald-600 text-white hover:bg-emerald-700',
                'deduct_todays_earnings' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                'deduct_on_hold' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
            ];
            $fundsConfirmLabel = match ($fundsAction) {
                'add_funds' => 'Add $'.$fundsAmountValue,
                'deduct_funds' => 'Deduct $'.$fundsAmountValue,
                'return_balance' => 'Return Balance',
                'add_todays_earnings' => 'Add $'.$fundsAmountValue.' to Today',
                'deduct_todays_earnings' => 'Deduct $'.$fundsAmountValue.' from Today',
                'add_on_hold' => 'Add $'.$fundsAmountValue.' to On-Hold',
                'deduct_on_hold' => 'Deduct $'.$fundsAmountValue.' from On-Hold',
                default => 'Confirm',
            };
        @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation" wire:click.self="closeFunds">
            <section role="dialog" aria-modal="true" aria-labelledby="manage-funds-title"
                class="relative w-full max-w-sm rounded-lg border border-border bg-card p-6 shadow-lg">
                <button type="button" wire:click="closeFunds" aria-label="Close manage funds"
                    class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground transition-opacity hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                </button>

                <header class="flex flex-col space-y-1.5 text-center sm:text-left">
                    <h2 id="manage-funds-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">Manage Funds</h2>
                    <p class="text-sm text-muted-foreground">{{ $fundsName }} — Balance: ${{ number_format($manageFundsUser->balance / 100, 2) }} · On Hold: ${{ number_format($fundsOnHold / 100, 2) }} · Today: ${{ number_format($manageFundsUser->daily_commission / 100, 2) }}</p>
                </header>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <button type="button" wire:click="$set('fundsAction', 'add_funds')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'add_funds' ? $fundsActionStyles['add_funds'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="plus" class="h-3 w-3" /> Add Funds
                        </button>
                        <button type="button" wire:click="$set('fundsAction', 'deduct_funds')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'deduct_funds' ? $fundsActionStyles['deduct_funds'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="minus" class="h-3 w-3" /> Deduct Funds
                        </button>
                        <button type="button" wire:click="$set('fundsAction', 'return_balance')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'return_balance' ? $fundsActionStyles['return_balance'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="circle-arrow-up" class="h-3 w-3" /> Return Balance
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 max-[420px]:grid-cols-1">
                        <button type="button" wire:click="$set('fundsAction', 'add_todays_earnings')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'add_todays_earnings' ? $fundsActionStyles['add_todays_earnings'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="plus" class="h-3 w-3" /> Add Today's Earnings
                        </button>
                        <button type="button" wire:click="$set('fundsAction', 'deduct_todays_earnings')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'deduct_todays_earnings' ? $fundsActionStyles['deduct_todays_earnings'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="minus" class="h-3 w-3" /> Deduct Today's Earnings
                        </button>
                        <button type="button" wire:click="$set('fundsAction', 'add_on_hold')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'add_on_hold' ? $fundsActionStyles['add_on_hold'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="plus" class="h-3 w-3" /> Add On-Hold
                        </button>
                        <button type="button" wire:click="$set('fundsAction', 'deduct_on_hold')"
                            class="{{ $fundsButtonBase }} {{ $fundsAction === 'deduct_on_hold' ? $fundsActionStyles['deduct_on_hold'] : $fundsButtonIdle }}">
                            <x-dashboard.icon name="minus" class="h-3 w-3" /> Deduct On-Hold
                        </button>
                    </div>

                    @if ($fundsAction === 'return_balance')
                        <p class="text-xs text-muted-foreground">This will add the on-hold balance of ${{ number_format($fundsOnHold / 100, 2) }} back to the user's main balance, auto-submit any pending product, and allow them to continue submitting tasks.</p>
                    @else
                        <div>
                            <label for="funds-amount" class="mb-1 block text-xs text-muted-foreground">Amount ($)</label>
                            <input wire:model.live.debounce.300ms="fundsAmount" id="funds-amount" type="number" min="0" step="0.01" placeholder="0.00"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm @error('fundsAmount') border-destructive @enderror" />
                            @error('fundsAmount')
                                <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeFunds"
                        class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        Cancel
                    </button>
                    <button type="button" wire:click="applyFunds" wire:loading.attr="disabled"
                        class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md px-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 {{ $fundsConfirmStyles[$fundsAction] ?? 'bg-primary text-primary-foreground hover:bg-primary/90' }}">
                        {{ $fundsConfirmLabel }}
                    </button>
                </div>
            </section>
        </div>
    @endif
</div>

@script
    <script>
        $wire.on('toast', (event) => {
            if (window.showToast) {
                window.showToast(event.message);
            }
        });
    </script>
@endscript
