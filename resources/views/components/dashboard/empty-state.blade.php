@props(['message', 'icon' => 'circle-check', 'iconSize' => 'h-10 w-10'])

<div {{ $attributes->merge(['class' => 'p-8 flex flex-col items-center justify-center text-center']) }}>
    <x-dashboard.icon :name="$icon" class="{{ $iconSize }} text-muted-foreground/30 mb-2" />
    <p class="text-sm text-muted-foreground">{{ $message }}</p>
</div>
