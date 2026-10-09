@php
    $typeStyles = [
        'announcement' => ['icon' => 'megaphone', 'class' => 'bg-primary/10 text-primary'],
        'maintenance' => ['icon' => 'bell', 'class' => 'bg-destructive/10 text-destructive'],
        'review' => ['icon' => 'star', 'class' => 'bg-amber-500/10 text-amber-600'],
        'update' => ['icon' => 'refresh-cw', 'class' => 'bg-emerald-500/10 text-emerald-600'],
        'approval' => ['icon' => 'bell', 'class' => 'bg-primary/10 text-primary'],
    ];
@endphp

<div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold">Notifications</h1>
            <p class="text-muted-foreground text-sm mt-1">Stay updated with announcements and alerts</p>
        </div>
        <div
            class="inline-flex shrink-0 items-center rounded-full border border-transparent bg-primary px-2.5 py-0.5 text-xs font-semibold text-primary-foreground">
            {{ $unreadCount }} unread
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            @php
                $isUnread = ! $notification->isRead();
                $style = $typeStyles[$notification->type] ?? $typeStyles['announcement'];
            @endphp
            <div wire:key="notification-{{ $notification->id }}"
                @class([
                    'card-elevated rounded-lg border text-card-foreground shadow-sm transition-all',
                    'border-primary/30 bg-primary/[0.02]' => $isUnread,
                    'bg-card' => ! $isUnread,
                ])>
                <div class="flex gap-4 p-6 py-4">
                    <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $style['class'] }}">
                        <x-dashboard.icon :name="$style['icon']" class="h-5 w-5" />
                    </div>

                    <button type="button" wire:click="markAsRead({{ $notification->id }})"
                        class="flex-1 min-w-0 text-left">
                        <div class="flex items-start justify-between gap-2">
                            <p @class([
                                'text-sm font-semibold',
                                'text-foreground' => $isUnread,
                                'text-foreground/80' => ! $isUnread,
                            ])>
                                {{ $notification->title }}@if ($isUnread)<span
                                    class="inline-block w-2 h-2 bg-primary rounded-full ml-2 align-middle"></span>@endif
                            </p>
                            <span class="text-xs text-muted-foreground shrink-0">
                                {{ $notification->created_at->format('Y-m-d') }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground mt-1">{{ $notification->body }}</p>
                    </button>

                    <button type="button" wire:click="delete({{ $notification->id }})"
                        wire:confirm="Delete this notification?" title="Delete notification"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-muted">
                        <x-dashboard.icon name="trash-2" class="h-4 w-4 text-muted-foreground" />
                    </button>
                </div>
            </div>
        @empty
            <div class="card-elevated rounded-lg border bg-card shadow-sm">
                <x-dashboard.empty-state message="No notifications yet." icon="bell" />
            </div>
        @endforelse
    </div>
</div>
