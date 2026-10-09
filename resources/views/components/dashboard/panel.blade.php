@props(['title', 'icon' => null, 'iconClass' => 'text-primary', 'action' => null, 'actionUrl' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-card shadow-sm']) }}>
    <div class="px-4 py-3 border-b border-border flex items-center justify-between">
        <h2 class="font-display text-sm font-bold text-foreground flex items-center gap-2">
            @if ($icon)
                <x-dashboard.icon :name="$icon" class="h-4 w-4 {{ $iconClass }}" />
            @endif
            {{ $title }}
        </h2>

        @if ($action)
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="text-xs text-primary font-medium hover:underline">{{ $action }}</a>
            @else
                <button type="button" class="text-xs text-primary font-medium hover:underline">{{ $action }}</button>
            @endif
        @endif
    </div>

    {{ $slot }}
</div>
