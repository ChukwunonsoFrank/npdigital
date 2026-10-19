<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.dashboard')] #[Title('Announcements')] class extends Component {
    public string $tab = 'announcement';

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $type = 'announcement';

    public string $title = '';

    public string $body = '';

    public bool $is_active = true;

    public bool $show_as_popup = false;

    public string $target_audience = 'all';

    /** @var list<int|string> */
    public array $selectedUserIds = [];

    public string $userSearch = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function create(): void
    {
        $this->closeForm();
        $this->type = $this->tab;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $item = Announcement::query()->findOrFail($id);
        $this->closeForm();
        $this->editingId = $item->id;
        $this->type = $item->type;
        $this->title = $item->title;
        $this->body = $item->body;
        $this->is_active = $item->is_active;
        $this->show_as_popup = $item->show_as_popup;
        $this->target_audience = $item->target_audience;
        $this->selectedUserIds = $item->recipients()->pluck('users.id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->title = trim($this->title);
        $this->body = trim($this->body);
        $data = $this->validate([
            'type' => ['required', Rule::in(['announcement', 'notification', 'review', 'update'])],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'is_active' => ['boolean'],
            'show_as_popup' => ['boolean'],
            'target_audience' => ['required', Rule::in(['all', 'specific'])],
            'selectedUserIds' => ['exclude_unless:target_audience,specific', 'required', 'array', 'min:1'],
            'selectedUserIds.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('is_admin', 0)],
        ], [
            'selectedUserIds.required' => 'Select at least one user.',
            'selectedUserIds.min' => 'Select at least one user.',
            'selectedUserIds.*.exists' => 'Select valid user accounts, not administrators.',
        ]);

        $recipientIds = $data['selectedUserIds'] ?? [];
        unset($data['selectedUserIds']);

        DB::transaction(function () use ($data, $recipientIds): void {
            $item = $this->editingId ? Announcement::query()->findOrFail($this->editingId) : new Announcement;
            $item->fill($data)->save();
            $item->recipients()->sync($recipientIds);
        });

        $this->tab = $this->type;
        $this->closeForm();
        $this->dispatch('notify', message: 'Content saved.', type: 'success');
    }

    public function toggleActive(int $id): void
    {
        $item = Announcement::query()->findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
    }

    public function delete(int $id): void
    {
        Announcement::query()->findOrFail($id)->delete();
    }

    public function closeForm(): void
    {
        $this->reset('showForm', 'editingId', 'type', 'title', 'body', 'is_active', 'show_as_popup', 'target_audience', 'selectedUserIds', 'userSearch');
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'items' => Announcement::query()->where('type', $this->tab)->latest()->orderByDesc('id')->get(),
            'counts' => Announcement::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'audienceUsers' => $this->showForm && $this->target_audience === 'specific'
                ? User::query()->where('is_admin', false)
                    ->when($this->userSearch !== '', fn ($query) => $query->where(function ($query): void {
                        $query->where('name', 'like', '%'.$this->userSearch.'%')
                            ->orWhere('username', 'like', '%'.$this->userSearch.'%');
                    }))
                    ->orderBy('id')->limit(50)->get(['id', 'name', 'username'])
                : collect(),
        ];
    }
}; ?>

<div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" wire:navigate aria-label="Back to overview" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="font-display text-xl font-bold">Content Management</h1>
    </div>
    <p class="text-sm text-muted-foreground">Create and manage announcements, notifications, reviews, and updates shown to users.</p>

    @php
        $tabs = [
            'announcement' => ['Announcements', 'megaphone', 'bg-primary/10 text-primary'],
            'notification' => ['Notifications', 'bell', 'bg-red-500/10 text-red-500'],
            'review' => ['Reviews', 'star-outline', 'bg-amber-500/10 text-amber-600'],
            'update' => ['Updates', 'refresh-cw', 'bg-emerald-500/10 text-emerald-600'],
        ];
    @endphp

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 max-w-full overflow-x-auto rounded-md bg-muted" x-data @keydown.arrow-right.prevent="$focus.wrap().next()" @keydown.arrow-left.prevent="$focus.wrap().previous()" @keydown.home.prevent="$focus.first()" @keydown.end.prevent="$focus.last()">
                <div role="tablist" aria-label="Content categories" class="inline-flex h-10 items-center p-1 text-muted-foreground">
                    @foreach ($tabs as $key => [$label, $icon, $color])
                        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="content-panel" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" tabindex="{{ $tab === $key ? '0' : '-1' }}" wire:click="$set('tab', '{{ $key }}')" @focus="$wire.set('tab', '{{ $key }}')" @class(['inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring', 'bg-background text-foreground shadow-sm' => $tab === $key])>
                            <x-dashboard.icon :name="$icon" class="h-3.5 w-3.5" />
                            {{ $label }}
                            <span class="ml-1 inline-flex h-4 items-center rounded-full bg-secondary px-1.5 text-[10px] font-semibold text-secondary-foreground">{{ $counts[$key] ?? 0 }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <button type="button" wire:click="create" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <x-dashboard.icon name="plus" class="mr-1 h-4 w-4" /> Create
            </button>
        </div>

        <div id="content-panel" role="tabpanel" aria-labelledby="tab-{{ $tab }}" tabindex="0" class="mt-4 space-y-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            @forelse ($items as $item)
                <article wire:key="content-{{ $item->id }}" @class(['flex items-start justify-between gap-3 rounded-xl border border-border bg-card p-4 transition-opacity', 'opacity-50' => ! $item->is_active])>
                    <div class="flex min-w-0 flex-1 gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $tabs[$item->type][2] }}">
                            <x-dashboard.icon :name="$tabs[$item->type][1]" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-sm font-semibold">{{ $item->title }}</h2>
                                @unless ($item->is_active)
                                    <span class="inline-flex shrink-0 items-center rounded-full border border-border px-2.5 py-0.5 text-[10px] font-semibold">Inactive</span>
                                @endunless
                            </div>
                            <p class="mt-0.5 whitespace-pre-line break-words text-sm text-muted-foreground">{{ $item->body }}</p>
                            <p class="mt-1 text-[11px] text-muted-foreground/60">{{ $item->created_at->format('Y-m-d') }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" wire:click="toggleActive({{ $item->id }})" wire:loading.attr="disabled" title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}" aria-label="{{ $item->is_active ? 'Deactivate' : 'Activate' }} {{ $item->title }}" aria-pressed="{{ $item->is_active ? 'true' : 'false' }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                            <span @class(['h-2 w-2 rounded-full', 'bg-emerald-500' => $item->is_active, 'bg-muted-foreground' => ! $item->is_active])></span>
                        </button>
                        <button type="button" wire:click="edit({{ $item->id }})" aria-label="Edit {{ $item->title }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">
                            <x-dashboard.icon name="pencil" class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Delete this content? This cannot be undone." wire:loading.attr="disabled" aria-label="Delete {{ $item->title }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-destructive hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                            <x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-border bg-card p-8 text-center text-sm text-muted-foreground">No {{ strtolower($tabs[$tab][0] ?? 'content') }} yet.</div>
            @endforelse
        </div>
    </div>

    @if ($showForm)
        @teleport('body')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" x-data x-trap.inert.noscroll="true" @keydown.escape.capture="$event.fromContentMenu = !!$el.querySelector('[popover]:popover-open')" @keydown.escape.window="if (!$event.fromContentMenu) $wire.closeForm()">
            <div class="absolute inset-0" wire:click="closeForm"></div>
            <section role="dialog" aria-modal="true" aria-labelledby="content-form-title" class="relative max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-lg border border-border bg-background p-6 shadow-lg">
                <button type="button" wire:click="closeForm" aria-label="Close dialog" class="absolute right-4 top-4 text-muted-foreground hover:text-foreground"><x-dashboard.icon name="x" class="h-4 w-4" /></button>
                <h2 id="content-form-title" class="pr-6 text-lg font-semibold leading-none tracking-tight">{{ $editingId ? 'Edit Content' : 'Create Content' }}</h2>
                <form wire:submit="save" class="mt-4 space-y-4">
                    <div class="space-y-3">
                        <flux:dropdown class="block w-full" x-data="{ width: 0 }" x-init="width = $el.getBoundingClientRect().width" @resize.window="width = $el.getBoundingClientRect().width">
                            <button type="button" aria-label="Content type" autofocus class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <span>{{ $tabs[$type][0] ?? 'Select type' }}</span><x-dashboard.icon name="chevron-down" class="h-4 w-4 opacity-50" />
                            </button>
                            <flux:menu x-bind:style="{ width: width + 'px' }" class="bg-card!">
                                <flux:menu.radio.group wire:model.live="type">
                                    @foreach ($tabs as $key => [$label])
                                        <flux:menu.radio :value="$key" class="data-checked:bg-emerald-500! data-checked:text-white! data-checked:**:data-flux-menu-item-icon:text-white!">{{ $label }}</flux:menu.radio>
                                    @endforeach
                                </flux:menu.radio.group>
                            </flux:menu>
                        </flux:dropdown>
                        <flux:error name="type" />
                        <flux:input wire:model="title" aria-label="Title" placeholder="Title" maxlength="255" required class="[&_input]:bg-background! [&_input]:shadow-none! [&_input]:border-input! [&_input]:placeholder:text-muted-foreground!" />
                        <flux:textarea wire:model="body" aria-label="Message" placeholder="Message" rows="3" maxlength="10000" required class="h-20 min-h-20 bg-background! shadow-none! border-input! placeholder:text-muted-foreground!" />

                        <div class="flex items-center justify-between gap-3 rounded-lg border border-border p-3">
                            <div class="space-y-0.5">
                                <label for="content-popup" class="flex items-center gap-1.5 text-sm font-medium"><x-dashboard.icon name="monitor-smartphone" class="h-4 w-4" />Show as Popup</label>
                                <p class="text-xs text-muted-foreground">Display as a popup dialog when users login</p>
                            </div>
                            <flux:switch id="content-popup" wire:model="show_as_popup" aria-label="Show as Popup" class="h-6! w-11! min-w-11! border-2 border-transparent bg-input! data-checked:bg-primary! [&>span]:size-5! [&>span]:translate-x-0! [&[data-checked]>span]:translate-x-5!" />
                        </div>
                        <div class="space-y-2 rounded-lg border border-border p-3">
                            <p id="audience-label" class="flex items-center gap-1.5 text-sm font-medium"><x-dashboard.icon name="users" class="h-4 w-4" />Target Audience</p>
                            <flux:dropdown class="block w-full" x-data="{ width: 0 }" x-init="width = $el.getBoundingClientRect().width" @resize.window="width = $el.getBoundingClientRect().width">
                                <button type="button" aria-labelledby="audience-label" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                    <span>{{ $target_audience === 'specific' ? 'Select Specific Users' : 'All Users' }}</span><x-dashboard.icon name="chevron-down" class="h-4 w-4 opacity-50" />
                                </button>
                                <flux:menu x-bind:style="{ width: width + 'px' }" class="bg-card!">
                                    <flux:menu.radio.group wire:model.live="target_audience">
                                        <flux:menu.radio value="all" class="data-checked:bg-emerald-500! data-checked:text-white! data-checked:**:data-flux-menu-item-icon:text-white!">All Users</flux:menu.radio>
                                        <flux:menu.radio value="specific" class="data-checked:bg-emerald-500! data-checked:text-white! data-checked:**:data-flux-menu-item-icon:text-white!">Select Specific Users</flux:menu.radio>
                                    </flux:menu.radio.group>
                                </flux:menu>
                            </flux:dropdown>
                            <flux:error name="target_audience" />
                            @if ($target_audience === 'specific')
                                <div class="space-y-2">
                                    <flux:input wire:model.live.debounce.300ms="userSearch" aria-label="Search users" placeholder="Search users..." size="sm" class="[&_input]:bg-background! [&_input]:shadow-none! [&_input]:border-input! [&_input]:placeholder:text-muted-foreground!" />
                                    <p role="status" class="text-xs text-muted-foreground">{{ count($selectedUserIds) }} {{ count($selectedUserIds) === 1 ? 'user' : 'users' }} selected</p>
                                    <div class="h-40 space-y-1 overflow-y-auto rounded-md border border-border p-2" aria-label="Available users">
                                        @forelse ($audienceUsers as $user)
                                            <label wire:key="audience-user-{{ $user->id }}" class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 hover:bg-muted">
                                                <flux:checkbox wire:model.live="selectedUserIds" :value="(string) $user->id" aria-label="Select {{ $user->name ?: $user->username }}" style="--color-accent: var(--color-primary); --color-accent-foreground: var(--color-primary-foreground)" class="size-4! [&_[data-flux-checkbox-indicator]]:size-4! [&_[data-flux-checkbox-indicator]]:rounded-full! [&_[data-flux-checkbox-indicator]]:border-primary!" />
                                                <span class="min-w-0 break-words text-sm">{{ $user->name ?: $user->username }}</span>
                                                <span class="ml-auto min-w-0 break-all text-xs text-muted-foreground">{{ '@'.$user->username }}</span>
                                            </label>
                                        @empty
                                            <p class="p-2 text-sm text-muted-foreground">No users found.</p>
                                        @endforelse
                                    </div>
                                    @if ($audienceUsers->count() === 50)
                                        <p class="text-xs text-muted-foreground">Showing the first 50 results. Search to find more users.</p>
                                    @endif
                                    <flux:error name="selectedUserIds" />
                                    <flux:error name="selectedUserIds.*" />
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeForm" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"><x-dashboard.icon name="save" class="mr-1 h-4 w-4" /><span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Create' }}</span><span wire:loading wire:target="save">Saving...</span></button>
                    </div>
                </form>
            </section>
        </div>
        @endteleport
    @endif
</div>
