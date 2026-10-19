<div class="space-y-5 animate-fade-in" x-data @notify.window="window.showToast($event.detail.message)">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" wire:navigate aria-label="Back to overview"
            class="inline-flex h-10 w-10 items-center justify-center rounded-md text-foreground transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="font-display text-xl font-bold text-foreground">Payment Management</h1>
        <span class="ml-auto inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">
            {{ $newCount + $completedCount }} Total
        </span>
    </div>

    <div class="relative">
        <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search users..."
            class="h-10 w-full rounded-md border-border bg-card py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" />
    </div>

    <div>
        <div class="grid w-full grid-cols-2 rounded-md bg-muted p-1" role="tablist" aria-label="Payment status">
            @foreach (['new' => ['clock', 'New', $newCount], 'completed' => ['circle-check', 'Completed', $completedCount]] as $value => [$icon, $label, $count])
                <button type="button" wire:click="selectStatus('{{ $value }}')" role="tab"
                    aria-selected="{{ $status === $value ? 'true' : 'false' }}"
                    @class([
                        'inline-flex h-8 items-center justify-center gap-2 rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        'bg-card text-foreground shadow-sm' => $status === $value,
                        'text-muted-foreground hover:text-foreground' => $status !== $value,
                    ])>
                    <x-dashboard.icon :name="$icon" class="h-4 w-4" />
                    {{ $label }}
                    <span class="inline-flex items-center rounded-full bg-secondary px-2 py-0.5 text-xs font-semibold text-secondary-foreground">{{ $count }}</span>
                </button>
            @endforeach
        </div>

        @if ($status === 'completed')
            <div class="mt-4 grid w-full grid-cols-2 rounded-md bg-muted p-1" role="tablist" aria-label="Payment history type">
                @foreach (['deposits' => ['arrow-down-to-line', 'Deposited History'], 'withdrawals' => ['arrow-up-from-line', 'Withdraw History']] as $value => [$icon, $label])
                    <button type="button" wire:click="selectHistoryType('{{ $value }}')" role="tab"
                        aria-selected="{{ $historyType === $value ? 'true' : 'false' }}"
                        @class([
                            'inline-flex h-8 items-center justify-center gap-2 rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            'bg-card text-foreground shadow-sm' => $historyType === $value,
                            'text-muted-foreground hover:text-foreground' => $historyType !== $value,
                        ])>
                        <x-dashboard.icon :name="$icon" class="h-4 w-4" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @error('transaction')
        <p role="alert" class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{{ $message }}</p>
    @enderror

    @if ($transactions->isEmpty())
        <div class="flex min-h-[172px] flex-col items-center justify-center rounded-xl border border-border bg-card p-12 text-center">
            <x-dashboard.icon :name="$status === 'new' ? 'clock' : 'users'" class="mx-auto mb-3 h-10 w-10 text-muted-foreground/30" />
            <p class="text-sm text-muted-foreground">
                {{ $status === 'new' ? 'No pending transactions' : ($historyType === 'deposits' ? 'No deposit history yet' : 'No withdraw history yet') }}
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">Transaction</th>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Method</th>
                            <th class="px-5 py-3">Amount</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($transactions as $transaction)
                            <tr wire:key="{{ strtolower($transaction['type']) }}-{{ $transaction['id'] }}">
                                <td class="px-5 py-4 font-medium text-foreground">{{ $transaction['type'] }} #{{ $transaction['id'] }}</td>
                                <td class="px-5 py-4 text-muted-foreground">{{ $transaction['user'] }}</td>
                                <td class="px-5 py-4 text-muted-foreground">{{ $transaction['method'] }}</td>
                                <td class="px-5 py-4 text-foreground">${{ number_format($transaction['amount'] / 100, 2) }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-secondary px-2 py-1 text-xs font-semibold text-secondary-foreground">{{ ucfirst($transaction['status']) }}</span></td>
                                <td class="px-5 py-4 text-right">
                                    @if ($transaction['status'] === 'pending')
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical"
                                                aria-label="Actions for {{ $transaction['type'] }} #{{ $transaction['id'] }}" />
                                            <flux:navmenu>
                                                <flux:navmenu.item icon="check"
                                                    wire:click="review('{{ $transaction['type'] }}', {{ $transaction['id'] }}, true)"
                                                    wire:confirm="Approve this {{ strtolower($transaction['type']) }}? The user will receive an email."
                                                    wire:loading.attr="disabled">Approve</flux:navmenu.item>
                                                <flux:navmenu.item icon="x-mark" variant="danger"
                                                    wire:click="review('{{ $transaction['type'] }}', {{ $transaction['id'] }}, false)"
                                                    wire:confirm="Decline this {{ strtolower($transaction['type']) }}? The user will receive an email."
                                                    wire:loading.attr="disabled">Decline</flux:navmenu.item>
                                            </flux:navmenu>
                                        </flux:dropdown>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
