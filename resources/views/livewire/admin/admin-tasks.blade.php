<div class="space-y-4" x-on:toast.self="window.showToast($event.detail.message)">
    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" aria-label="Back to overview"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
            </a>
            <h1 class="font-display text-2xl font-bold">Product Tasks</h1>
        </div>
        @if ($tab === 'assignments')
            <button type="button" wire:click="openAssignment"
                class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-full bg-[hsl(190,80%,50%)] px-4 text-sm font-medium text-white transition-colors hover:bg-[hsl(190,80%,45%)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:px-6">
                <x-dashboard.icon name="plus" class="h-4 w-4" /> Create
            </button>
        @endif
    </div>

    <div>
        <div role="tablist" aria-label="Product tasks" class="inline-flex h-10 items-center rounded-md bg-muted p-1 text-muted-foreground"
            x-on:keydown.arrow-right.prevent="$wire.selectTab('history'); $refs.historyTab.focus()"
            x-on:keydown.arrow-left.prevent="$wire.selectTab('assignments'); $refs.assignmentsTab.focus()">
            @foreach (['assignments' => 'Assignments', 'history' => 'Tasks History'] as $value => $label)
                <button type="button" role="tab" id="{{ $value }}-tab" x-ref="{{ $value }}Tab"
                    aria-controls="{{ $value }}-panel" aria-selected="{{ $tab === $value ? 'true' : 'false' }}"
                    tabindex="{{ $tab === $value ? 0 : -1 }}" wire:click="selectTab('{{ $value }}')"
                    @class([
                        'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        'bg-background text-foreground shadow-sm' => $tab === $value,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        <section id="{{ $tab }}-panel" role="tabpanel" aria-labelledby="{{ $tab }}-tab" class="mt-2 space-y-4">
            <div class="relative">
                <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input wire:model.live.debounce.300ms="search" type="search" aria-label="Search members"
                    placeholder="Search members..."
                    class="h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-base placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:text-sm" />
            </div>

            @if ($tab === 'assignments')
                <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2">
                    @foreach ([false, true] as $isTraining)
                    <section aria-label="{{ $isTraining ? 'Virtual Accounts' : 'Real Accounts' }}" class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span @class(['h-2.5 w-2.5 rounded-full', 'bg-emerald-500' => ! $isTraining, 'bg-amber-500' => $isTraining])></span>
                            <h3 class="text-sm font-semibold">{{ $isTraining ? 'Virtual Accounts' : 'Real Accounts' }}</h3>
                            <span class="text-xs text-muted-foreground">({{ $isTraining ? $virtualCount : $realCount }})</span>
                        </div>
                    <div class="divide-y divide-border rounded-xl border border-border bg-card">
                        @forelse ($assignments->where('is_training', $isTraining) as $user)
                            @php
                                $group = $user->warehouseGroup();
                                $level = strtolower($user->membership_level);
                                $levelColor = match ($level) {
                                    'normal' => 'bg-[hsl(210,50%,35%)]',
                                    'silver' => 'bg-slate-500',
                                    'gold' => 'bg-orange-500',
                                    'platinum' => 'bg-purple-600',
                                    'diamond' => 'bg-blue-600',
                                    default => 'bg-primary',
                                };
                            @endphp
                            <div wire:key="assignment-{{ $user->id }}" class="px-4 py-3">
                                    <button type="button" wire:click="$dispatchTo('admin.users', 'show-task-user', { userId: {{ $user->id }} })"
                                        class="flex w-full items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <span @class(['flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white', 'bg-primary' => ! $user->is_training, 'bg-[#27ab8f]' => $user->is_training])>{{ $user->initials() ?: strtoupper(substr($user->username, 0, 2)) }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-2">
                                                <span class="truncate text-sm font-semibold">{{ $user->name ?: $user->username }}</span>
                                                <span @class(['shrink-0 rounded-full px-1.5 text-[10px] font-semibold', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => ! $user->is_training, 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $user->is_training])>{{ $user->is_training ? 'VIRTUAL' : 'REAL' }}</span>
                                            </span>
                                            <span class="mt-0.5 flex items-center gap-1.5">
                                                @if ($group !== null)
                                                <span class="rounded-full bg-emerald-100 px-1.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">AUTO</span>
                                                <span class="text-[11px] text-muted-foreground">Day {{ $group['day'] }} {{ strtoupper($group['group']) }}</span>
                                                @else
                                                <span class="text-[11px] text-muted-foreground">Not assigned</span>
                                                @endif
                                            </span>
                                        </span>
                                        <span class="flex shrink-0 flex-col items-end gap-1">
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white {{ $levelColor }}">{{ strtoupper($user->membership_level) }}</span>
                                            <span class="text-[11px] font-bold text-amber-600">{{ $user->tasks_completed }}/{{ $user->productsPerSet() }}</span>
                                        </span>
                                    </button>
                            </div>
                        @empty
                            <p class="p-6 text-center text-sm text-muted-foreground">No {{ $isTraining ? 'virtual' : 'real' }} accounts found.</p>
                        @endforelse
                    </div>
                    </section>
                    @endforeach
                </div>
            @else
                <div class="space-y-3 md:hidden">
                    @forelse ($members as $member)
                        <div wire:key="history-mobile-{{ $member->id }}" class="rounded-xl border border-border bg-card p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $member->name ?: $member->username }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ '@'.$member->username }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">{{ ucfirst($member->membership_level) }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                <div><p class="text-[10px] text-muted-foreground uppercase">Submissions</p><p class="font-bold text-primary">{{ $member->submissions_count }}</p></div>
                                <div><p class="text-[10px] text-muted-foreground uppercase">Joined</p><p class="text-xs font-semibold">{{ $member->created_at->format('M j, Y') }}</p></div>
                                <div><p class="text-[10px] text-muted-foreground uppercase">Last submitted</p><p class="text-xs font-semibold">{{ $member->last_submitted_at?->format('M j, Y') ?? '—' }}</p></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl border border-border bg-card p-6 text-center text-sm text-muted-foreground">No members found.</p>
                    @endforelse
                </div>
                <div class="hidden overflow-hidden rounded-xl border border-border bg-card md:block">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr><th class="px-4 py-3 text-left">Member</th><th class="px-4 py-3 text-left">Level</th><th class="px-4 py-3 text-right">Submissions</th><th class="px-4 py-3 text-left">Joined</th><th class="px-4 py-3 text-left">Last submitted</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($members as $member)
                                <tr wire:key="history-desktop-{{ $member->id }}" class="border-t border-border/60">
                                    <td class="px-4 py-3"><p class="font-semibold">{{ $member->name ?: $member->username }}</p><p class="text-xs text-muted-foreground">{{ '@'.$member->username }}{{ $member->is_training ? ' · training' : '' }}</p></td>
                                    <td class="px-4 py-3">{{ ucfirst($member->membership_level) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-primary">{{ $member->submissions_count }}</td>
                                    <td class="px-4 py-3">{{ $member->created_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">{{ $member->last_submitted_at?->format('M j, Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-6 text-center text-muted-foreground">No members found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $members->links() }}
            @endif
        </section>
    </div>

    <livewire:admin.users :task-summary="true" @task-user-updated="$refresh" />

    @if ($showAssignmentModal)
        @teleport('body')
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/70 p-4"
            wire:click.self="$set('showAssignmentModal', false)" x-on:keydown.escape.window="$wire.set('showAssignmentModal', false)">
            <section role="dialog" aria-modal="true" aria-labelledby="assignment-title" x-trap.inert.noscroll="true"
                class="relative max-h-[90vh] w-full max-w-md overflow-hidden rounded-xl border border-border bg-card p-6 text-foreground shadow-lg">
                <button type="button" wire:click="$set('showAssignmentModal', false)" aria-label="Close assignment" class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground hover:text-foreground"><x-dashboard.icon name="x" class="h-4 w-4" /></button>
                <form wire:submit="{{ $assignmentStep === 3 ? 'saveAssignment' : 'nextAssignmentStep' }}" class="flex max-h-[calc(90vh-3rem)] flex-col gap-6">
                    <h2 id="assignment-title" class="shrink-0 pr-4 text-lg font-bold">Step {{ $assignmentStep }}: {{ [1 => 'Select Users', 2 => 'Select Group', 3 => 'Premium Product (Optional)'][$assignmentStep] ?? 'Select Users' }}</h2>

                    <div class="min-h-0 space-y-6 overflow-y-auto">
                    @if ($selectedAssignmentUsers->isNotEmpty())
                        <section aria-label="Selected user balance" class="space-y-3 rounded-xl border border-border bg-muted/20 p-3">
                            <div class="flex items-center justify-between gap-2"><h3 class="text-sm font-bold">Selected user balance</h3><span class="text-xs text-muted-foreground">{{ $selectedAssignmentUsers->count() }} selected</span></div>
                            <div class="max-h-40 space-y-2 overflow-y-auto">
                                @foreach ($selectedAssignmentUsers as $user)
                                    <div wire:key="selected-assignment-{{ $user->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-border p-3">
                                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="break-words text-sm">{{ $user->name ?: $user->username }}</span><span class="rounded-full bg-secondary px-2 py-1 text-[10px] font-bold">{{ $user->is_training ? 'VIRTUAL' : 'REAL' }}</span></div><p class="break-all text-xs text-muted-foreground">{{ '@'.$user->username }}</p></div>
                                        <span class="shrink-0 text-sm font-bold">${{ number_format($user->balance / 100, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($assignmentStep === 1)
                        <div class="space-y-2">
                            <flux:input label="Select Users" wire:model.live.debounce.300ms="assignmentSearch" icon="magnifying-glass" placeholder="Search by username or referral code" />
                            <div class="max-h-72 space-y-1 overflow-y-auto rounded-xl border border-border p-2" aria-label="Available users">
                                @forelse ($assignmentUsers as $user)
                                    @php
                                        $vip = array_search(strtolower($user->membership_level), ['normal', 'silver', 'gold', 'platinum', 'diamond']);
                                        $vipColor = match (strtolower($user->membership_level)) { 'silver' => 'bg-slate-500', 'gold' => 'bg-orange-500', 'platinum' => 'bg-purple-600', 'diamond' => 'bg-blue-600', default => 'bg-[hsl(210,60%,35%)]' };
                                    @endphp
                                    <label wire:key="assignment-user-{{ $user->id }}" class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-3 hover:bg-muted">
                                        <flux:checkbox wire:model.live="assignmentUserIds" :value="(string) $user->id" aria-label="Select {{ $user->name ?: $user->username }}" style="--color-accent: var(--color-primary); --color-accent-foreground: var(--color-primary-foreground)" class="size-4! [&_[data-flux-checkbox-indicator]]:size-4! [&_[data-flux-checkbox-indicator]]:rounded-full! [&_[data-flux-checkbox-indicator]]:border-primary!" />
                                        <span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-2"><span class="break-words text-sm">{{ $user->name ?: $user->username }}</span><span class="rounded-full bg-secondary px-2 py-1 text-[10px] font-bold">{{ $user->is_training ? 'VIRTUAL' : 'REAL' }}</span></span><span class="block text-xs text-muted-foreground">Balance: ${{ number_format($user->balance / 100, 2) }}</span></span>
                                        <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold text-white {{ $vipColor }}">VIP {{ $vip === false ? '—' : $vip + 1 }}</span>
                                    </label>
                                @empty
                                    <p class="p-3 text-sm text-muted-foreground">No users found.</p>
                                @endforelse
                            </div>
                            @if ($assignmentUsers->count() === 50)<p class="text-xs text-muted-foreground">Showing the first 50 results. Search to find more users.</p>@endif
                            <p role="status" class="text-xs text-muted-foreground">{{ count($assignmentUserIds) }} user(s) selected</p>
                        </div>
                    @elseif ($assignmentStep === 2)
                        <p class="rounded-xl bg-blue-50 p-3 text-sm text-blue-600 dark:bg-blue-950 dark:text-blue-300">{{ count($assignmentUserIds) }} user(s) selected — {{ ucfirst($selectedAssignmentUsers->first()?->membership_level ?? '') }} Level</p>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold">Select Group</label>
                            <flux:dropdown position="bottom" align="start" x-data="{ width: 0 }" x-on:click="width = $el.getBoundingClientRect().width" class="w-full">
                                <button type="button" class="flex h-10 w-full items-center justify-between rounded-lg border border-input bg-background px-3 text-sm">{{ $assignmentSelection ? 'Day '.$assignmentDay.' '.strtoupper($assignmentGroup) : 'Choose a group' }}<x-dashboard.icon name="chevron-down" class="h-4 w-4 text-muted-foreground" /></button>
                                <flux:menu x-bind:style="{ width: width + 'px' }" class="max-h-80 overflow-y-auto bg-card!">
                                    <flux:menu.radio.group wire:model.live="assignmentSelection" class="block">
                                        @for ($day = 1; $day <= \App\Support\ProductWarehouse::DAY_COUNT; $day++)
                                            @foreach (\App\Support\ProductWarehouse::GROUPS as $group)
                                                <flux:menu.radio value="{{ $day }}-{{ $group }}" class="data-checked:bg-emerald-500! data-checked:text-white!">Day {{ $day }} {{ strtoupper($group) }}</flux:menu.radio>
                                            @endforeach
                                        @endfor
                                    </flux:menu.radio.group>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    @else
                        <flux:checkbox wire:model.live="addPremium" label="Add a premium product" style="--color-accent: var(--color-primary); --color-accent-foreground: var(--color-primary-foreground)" class="[&_[data-flux-checkbox-indicator]]:rounded-full!" />
                        @if ($addPremium)
                            @php $premiumProduct = collect($premiumProducts)->firstWhere('id', $premiumProductId); @endphp
                            <div class="space-y-3 rounded-xl border border-border p-3">
                                <label class="text-xs">Premium Product ({{ ucfirst($selectedAssignmentUsers->first()?->membership_level ?? '') }} level)</label>
                                <flux:dropdown position="bottom" align="start" x-data="{ width: 0 }" x-on:click="width = $el.getBoundingClientRect().width" class="w-full">
                                    <button type="button" class="flex min-h-10 w-full items-center justify-between gap-2 rounded-lg border border-input bg-background px-3 py-2 text-left text-sm"><span>{{ $premiumProduct ? $premiumProduct['name'].($premiumProduct['name2'] ? ' + '.$premiumProduct['name2'] : '') : 'Select product' }}</span><x-dashboard.icon name="chevron-down" class="h-4 w-4 shrink-0 text-muted-foreground" /></button>
                                    <flux:menu x-bind:style="{ width: width + 'px' }" class="max-h-72 overflow-y-auto bg-card!">
                                        <flux:menu.radio.group wire:model.live="premiumProductId" class="block">
                                            @foreach ($premiumProducts as $product)
                                                <flux:menu.radio :value="$product['id']" class="whitespace-normal! data-checked:bg-emerald-500! data-checked:text-white!">{{ $product['name'] }}{{ $product['name2'] ? ' + '.$product['name2'] : '' }}</flux:menu.radio>
                                            @endforeach
                                        </flux:menu.radio.group>
                                    </flux:menu>
                                </flux:dropdown>
                                @if ($premiumProducts === [])<p class="text-sm text-muted-foreground">No premium products available for this level.</p>@endif
                                @if ($premiumProduct)
                                    <div wire:key="premium-preview-{{ $premiumProductId }}" class="space-y-3 rounded-xl border border-amber-300 bg-amber-500/5 p-3">
                                        <span class="inline-block rounded-full bg-amber-500 px-3 py-1 text-[10px] font-bold text-white">PREMIUM {{ $premiumProduct['name2'] ? 'COMBO' : 'PRODUCT' }}</span>
                                        @foreach (['' => 'premiumPrice', '2' => 'premiumPrice2'] as $suffix => $priceProperty)
                                            @if ($premiumProduct['name'.$suffix])
                                                <div class="flex items-center gap-3">
                                                    @if ($premiumProduct['image_url'.$suffix])
                                                        <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-md">
                                                            <img src="{{ $premiumProduct['image_url'.$suffix] }}" alt="{{ $premiumProduct['name'.$suffix] }}" class="h-full w-full object-cover" />
                                                            <x-product-brand-logo :brand="$premiumProduct['brand'.$suffix]" size="sm" />
                                                        </div>
                                                    @endif
                                                    <div class="min-w-0 flex-1 space-y-1"><p class="break-words text-sm font-bold">{{ $premiumProduct['name'.$suffix] }}</p><flux:input wire:model.live.debounce.300ms="{{ $priceProperty }}" aria-label="{{ $premiumProduct['name'.$suffix] }} price" type="number" min="0.01" step="0.01" class="max-w-28" /></div>
                                                </div>
                                            @endif
                                        @endforeach
                                        <div class="grid grid-cols-2 gap-3 border-t border-amber-300 pt-3">
                                            <flux:input wire:model="premiumPosition" label="Position" type="number" min="1" />
                                            <flux:input wire:model.live.debounce.300ms="premiumCommission" label="Commission (%)" type="number" min="0" max="100" step="0.01" />
                                        </div>
                                        @php $combined = (is_numeric($premiumPrice) ? (float) $premiumPrice : 0) + ($premiumProduct['name2'] && is_numeric($premiumPrice2) ? (float) $premiumPrice2 : 0); @endphp
                                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Combined: ${{ number_format($combined, 2) }} · Total Profit: ${{ number_format($combined * (is_numeric($premiumCommission) ? (float) $premiumCommission : 0) / 100, 2) }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                    @if ($errors->any())<div role="alert" class="space-y-1 text-sm text-destructive">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                    </div>
                    <div class="grid shrink-0 grid-cols-2 gap-2">
                        <button type="button" wire:click="{{ $assignmentStep === 1 ? '$set(\'showAssignmentModal\', false)' : 'previousAssignmentStep' }}" class="h-10 rounded-lg border border-input bg-background text-sm hover:bg-muted">{{ $assignmentStep === 1 ? 'Cancel' : 'Back' }}</button>
                        <button type="submit" wire:loading.attr="disabled" class="h-10 rounded-lg bg-[hsl(190,100%,44%)] text-sm font-medium text-white hover:bg-[hsl(190,100%,39%)] disabled:opacity-50">{{ $assignmentStep === 3 ? 'Assign' : 'Next' }}</button>
                    </div>
                </form>
            </section>
        </div>
        @endteleport
    @endif
</div>
