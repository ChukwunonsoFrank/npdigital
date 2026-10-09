<div class="mx-auto w-full max-w-4xl space-y-6 animate-fade-in">
    <div class="flex items-center gap-3">
        <x-dashboard.icon name="sparkles" class="h-7 w-7 text-primary" />
        <div>
            <h1 class="text-2xl font-bold text-foreground">Daily Events</h1>
            <p class="text-sm text-muted-foreground">Check in, spin the wheel, and open mystery boxes</p>
        </div>
    </div>

    @forelse ($events as $event)
        <div wire:key="event-{{ $event->id }}" class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <img src="{{ route('storage.file', $event->poster_image_path) }}" alt="Event flyer" class="w-full">
        </div>
    @empty
        <div class="rounded-xl border border-border bg-card py-16 text-center">
            <x-dashboard.icon name="sparkles" class="mx-auto mb-3 h-12 w-12 text-muted-foreground/40" />
            <p class="text-muted-foreground">No events available at the moment.</p>
        </div>
    @endforelse
</div>
