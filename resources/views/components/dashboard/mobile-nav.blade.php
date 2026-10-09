@props(['nav' => []])

@php
    $items = collect($nav)->filter(fn ($item) => $item['mobile'] ?? false)->values();
@endphp

<nav data-sidebar="mobile-nav"
    class="fixed inset-x-0 bottom-0 z-30 border-t border-sidebar-border bg-sidebar pb-[env(safe-area-inset-bottom)] text-sidebar-foreground md:hidden">
    <ul class="flex items-stretch justify-around px-1">
        @foreach ($items as $item)
            @php
                $url = ! empty($item['route']) ? route($item['route']) : '#';
                $isActive = ! empty($item['active']) && request()->routeIs($item['active']);
            @endphp
            <li class="flex-1">
                <a href="{{ $url }}" wire:navigate aria-current="{{ $isActive ? 'page' : 'false' }}"
                    class="flex flex-col items-center gap-1 py-2 text-[10px] font-semibold">
                    <span @class([
                        'flex h-8 w-12 items-center justify-center rounded-xl transition-colors',
                        'bg-sidebar-primary/20 text-sidebar-primary' => $isActive,
                        'text-sidebar-foreground/60' => ! $isActive,
                    ])>
                        <x-dashboard.icon :name="$item['icon']" class="h-5 w-5" />
                    </span>
                    <span @class([
                        'truncate' => true,
                        'text-sidebar-primary' => $isActive,
                        'text-sidebar-foreground/60' => ! $isActive,
                    ])>{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
