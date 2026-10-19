<div class="space-y-6 animate-fade-in">
    <div class="grid grid-cols-2 gap-3">
        <x-dashboard.stat-card label="Total Users" :value="$totalUsers" icon="users" />
        <x-dashboard.stat-card label="Total Products" :value="$totalProducts" icon="package" />
    </div>

    <x-dashboard.panel title="Incoming Messages" icon="message-circle" action="View All Chats">
        <x-dashboard.empty-state message="No new messages" />
    </x-dashboard.panel>

    <x-dashboard.panel title="Upgrade Requests" icon="circle-arrow-up" icon-class="text-purple-500" action="View All" :action-url="route('admin.upgrades')">
        <div class="divide-y divide-border">
            @forelse ($upgradeRequests as $request)
                <div wire:key="upgrade-{{ $request->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-4">
                    <div class="min-w-0 space-y-1">
                        <p class="break-words text-sm font-semibold">{{ $request->user->name ?: $request->user->username }} <span class="text-xs font-normal text-muted-foreground">Request #{{ $request->id }}</span></p>
                        <p class="text-sm text-muted-foreground">{{ $request->from_level }} → {{ $request->membershipLevel->name }}</p>
                        <p class="text-xs text-muted-foreground">{{ $request->created_at->format('M j, Y H:i') }}</p>
                    </div>
                    <span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold capitalize text-secondary-foreground">{{ $request->status }}</span>
                </div>
            @empty
                <x-dashboard.empty-state message="No upgrade requests yet" />
            @endforelse
        </div>
    </x-dashboard.panel>

    <x-dashboard.panel title="Latest Transactions" action="View All" :action-url="route('admin.payments')">
        <div class="divide-y divide-border">
            @forelse ($transactions as $transaction)
                <div wire:key="{{ $transaction->getTable() }}-{{ $transaction->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-4">
                    <div class="min-w-0 space-y-1">
                        <p class="text-sm font-semibold">{{ $transaction instanceof \App\Models\Deposit ? 'Deposit' : 'Withdrawal' }} #{{ $transaction->id }}</p>
                        <p class="break-words text-sm text-muted-foreground">{{ $transaction->user->name ?: $transaction->user->username }} · {{ $transaction->cryptocurrency ?? $transaction->payment_method }}</p>
                        <p class="text-xs text-muted-foreground">{{ $transaction->created_at->format('M j, Y H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold">${{ number_format($transaction->amount / 100, 2) }}</span>
                        <span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold capitalize text-secondary-foreground">{{ $transaction->status }}</span>
                    </div>
                </div>
            @empty
                <x-dashboard.empty-state message="No transactions yet" />
            @endforelse
        </div>
    </x-dashboard.panel>
</div>
