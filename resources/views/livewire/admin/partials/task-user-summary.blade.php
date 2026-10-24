@php
    $name = $selectedUser->name ?: $selectedUser->username;
    $level = strtolower($selectedUser->membership_level);
    $vipLevel = match ($level) {
        'normal' => 1,
        'silver' => 2,
        'gold' => 3,
        'platinum' => 4,
        'diamond' => 5,
        default => null,
    };
    $membershipColor = match ($level) {
        'gold' => 'bg-orange-500',
        'diamond' => 'bg-blue-600',
        'platinum' => 'bg-purple-600',
        'silver' => 'bg-slate-500',
        default => 'bg-primary',
    };
@endphp

<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" wire:click.self="closeUserDetails">
    <section role="dialog" aria-modal="true" aria-labelledby="task-user-title"
        class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl border border-border bg-card p-6 shadow-lg">
        <button type="button" wire:click="closeUserDetails" aria-label="Close user details"
            class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <x-dashboard.icon name="x" class="h-4 w-4" />
        </button>

        <header class="flex flex-wrap items-center gap-2 pr-6">
            <x-dashboard.icon name="user" class="h-5 w-5 shrink-0" />
            <h2 id="task-user-title" class="min-w-0 break-words text-lg font-bold">{{ $name }}</h2>
            <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold text-secondary-foreground">{{ $selectedUser->is_training ? 'Virtual/Training' : 'Real Account' }}</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white {{ $membershipColor }}">{{ $vipLevel ? 'VIP Level '.$vipLevel : $selectedUser->membership_level }}</span>
        </header>

        <div class="mt-4 grid grid-cols-2 gap-3 text-center">
            <div class="space-y-1 rounded-xl bg-blue-50 p-3 text-blue-600 dark:bg-blue-950 dark:text-blue-300">
                <p class="text-xs">Balance</p>
                <p class="break-words text-lg font-bold">${{ number_format($selectedUser->balance / 100, 2) }}</p>
            </div>
            <div class="space-y-1 rounded-xl bg-amber-50 p-3 text-orange-600 dark:bg-amber-950 dark:text-orange-300">
                <p class="text-xs">On Hold</p>
                <p class="break-words text-lg font-bold">${{ number_format(($selectedUser->lien_status === 'on_hold' ? $selectedUser->lien_amount : 0) / 100, 2) }}</p>
            </div>
            <div class="space-y-1 rounded-xl bg-amber-50 p-3 text-orange-600 dark:bg-amber-950 dark:text-orange-300">
                <p class="text-xs">Total Submitted</p>
                <p class="text-lg font-bold">{{ $selectedUser->tasks_completed }} / {{ $selectedUser->productsPerSet() }}</p>
            </div>
            <div class="space-y-1 rounded-xl bg-amber-50 p-3 text-orange-600 dark:bg-amber-950 dark:text-orange-300">
                <p class="text-xs">Premium Tasks</p>
                <p class="text-lg font-bold">{{ $exclusiveTaskCount }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-1.5">
            <button type="button" wire:click="openAddExclusive" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-green-600 px-3 text-xs font-medium text-white hover:bg-green-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="plus" class="h-4 w-4" /> Add Premium
            </button>
            <button type="button" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-amber-500 px-3 text-xs font-medium text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="x" class="h-4 w-4" /> Clear Negative
            </button>
            <button type="button" wire:click="clearRecord" wire:confirm="CLEAR RECORD: This will permanently reset the submission counter to 0, delete ALL submission records, and clear the on-hold balance. This action is IRREVERSIBLE. Continue?" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-blue-500 px-3 text-xs font-medium text-white hover:bg-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="file-text" class="h-4 w-4" /> Clear Record
            </button>
            <button type="button" wire:click="deleteUser" wire:confirm="DELETE ACCOUNT: This will permanently delete {{ $name }}'s account and ALL related records. This action is IRREVERSIBLE. Continue?" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-red-600 px-3 text-xs font-medium text-white hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="trash-2" class="h-4 w-4" /> Delete
            </button>
            <button type="button" wire:click="openUpgradeTarget" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-purple-600 px-3 text-xs font-medium text-white hover:bg-purple-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="circle-arrow-up" class="h-4 w-4" /> Set Upgrade
            </button>
            <button type="button" wire:click="openUpgradeMembership" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-cyan-600 px-3 text-xs font-medium text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="circle-arrow-up" class="h-4 w-4" /> Upgrade
            </button>
        </div>
    </section>
</div>
