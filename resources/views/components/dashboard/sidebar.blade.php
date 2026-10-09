@props(['nav' => [], 'variant' => 'user', 'profile' => null])

<aside :class="{
        'translate-x-0': open,
        '-translate-x-full': !open,
        'md:w-16': collapsed,
        'md:w-64': !collapsed
    }"
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-sidebar text-sidebar-foreground transition-[transform,width] duration-200 ease-linear md:static md:z-auto md:translate-x-0">
    <div class="flex h-full w-full flex-col bg-sidebar">
        <div class="p-4">
            <div class="flex items-center gap-2">
                <div class="inline-flex h-12 min-w-0 shrink-0 items-center justify-start bg-transparent"
                    :class="collapsed ? 'md:w-auto' : 'md:w-full'" aria-label="NP Digital" role="img">
                    <img alt="NP Digital" class="block h-full w-auto max-w-full object-contain object-left"
                        :class="collapsed ? 'md:hidden' : ''" src="{{ asset('assets/np/logo-inverse.png') }}">
                    <span
                        class="hidden h-9 w-9 items-center justify-center rounded-md bg-sidebar-accent text-xs font-bold text-sidebar-foreground"
                        :class="collapsed ? 'md:flex' : 'md:hidden'">NP</span>
                </div>
                @if ($variant === 'admin')
                    <span class="text-[10px] text-sidebar-foreground/50" :class="collapsed ? 'md:hidden' : ''">Admin</span>
                @endif
            </div>
        </div>

        <div data-sidebar="group" class="relative flex w-full min-w-0 flex-col p-2">
            <div data-sidebar="group-content" class="w-full text-sm">
                <ul data-sidebar="menu" class="flex w-full min-w-0 flex-col gap-1">
                    @foreach ($nav as $item)
                        <x-dashboard.nav-item :item="$item" :wire:key="'nav-' . ($item['route'] ?? $item['label'])" />
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-auto p-4">
            @if ($profile)
                <div class="mb-3 flex items-center gap-3 px-2">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sidebar-accent text-xs font-bold text-sidebar-accent-foreground">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profile['name'] ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0" :class="collapsed ? 'md:hidden' : ''">
                        <p class="truncate text-sm font-bold text-sidebar-foreground">{{ $profile['name'] }}</p>
                        <p class="truncate text-[11px] text-sidebar-foreground/50">{{ $profile['meta'] }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="inline-flex h-9 w-full items-center justify-start gap-2 whitespace-nowrap rounded-md px-3 text-sm font-bold text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-dashboard.icon name="log-out" class="mr-2 h-4 w-4" />
                    <span :class="collapsed ? 'md:hidden' : ''">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
