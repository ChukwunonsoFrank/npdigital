@props(['label', 'value', 'icon' => 'users'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-card p-4 flex items-center justify-between shadow-sm']) }}>
    <div>
        <p class="text-xs font-medium text-primary uppercase tracking-wider">{{ $label }}</p>
        <p class="text-2xl font-bold font-display text-foreground mt-1">{{ $value }}</p>
    </div>
    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
        <x-dashboard.icon :name="$icon" class="h-5 w-5 text-primary" />
    </div>
</div>
