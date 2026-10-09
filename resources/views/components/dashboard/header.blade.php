@props(['variant' => 'user'])

<header class="flex h-14 shrink-0 items-center border-b border-border bg-card px-4">
    <button type="button" @click="window.innerWidth >= 768 ? collapsed = !collapsed : open = !open"
        @class([
            'mr-4 h-7 w-7 items-center justify-center gap-2 rounded-md text-sm font-medium transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
            'hidden md:inline-flex' => $variant === 'user',
            'inline-flex' => $variant !== 'user',
        ])
        data-sidebar="trigger">
        <x-dashboard.icon name="panel-left" class="h-4 w-4" />
        <span class="sr-only">Toggle Sidebar</span>
    </button>

    <div class="inline-flex h-11 min-w-0 shrink-0 items-center justify-center bg-transparent sm:h-12" aria-label="NP Digital"
        role="img">
        <img alt="NP Digital" class="block h-full w-auto max-w-full object-contain object-left"
            src="{{ asset('assets/np/logo-color.png') }}">
    </div>

    @isset($actions)
        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">{{ $actions }}</div>
    @endisset
</header>
