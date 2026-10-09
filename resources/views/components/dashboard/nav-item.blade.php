@props(['item'])

@php
    $url = ! empty($item['route']) ? route($item['route']) : '#';
    $isActive = ! empty($item['active']) && request()->routeIs($item['active']);
    $base = 'peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left outline-none ring-sidebar-ring transition-[width,height,padding] focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground h-8 text-sm [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 hover:text-sidebar-accent-foreground';
@endphp

<li data-sidebar="menu-item" class="group/menu-item relative" {{ $attributes }}>
    <a href="{{ $url }}" wire:navigate data-sidebar="menu-button" data-size="default"
        data-active="{{ $isActive ? 'true' : 'false' }}"
        aria-current="{{ $isActive ? 'page' : 'false' }}"
        @class([
            $base,
            'bg-sidebar-accent text-sidebar-primary font-extrabold active' => $isActive,
            'font-bold text-sidebar-foreground hover:bg-sidebar-accent/50' => ! $isActive,
        ]) :class="collapsed ? 'md:justify-center' : ''">
        <x-dashboard.icon :name="$item['icon']" class="h-4 w-4" />
        <span :class="collapsed ? 'md:hidden' : ''">{{ $item['label'] }}</span>
    </a>
</li>
