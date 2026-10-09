@php
    $routeName = request()->route()?->getName() ?? '';
    $variant = str_starts_with($routeName, 'admin.') ? 'admin' : 'user';
    $nav = $variant === 'admin' ? \App\Support\Navigation::admin() : \App\Support\Navigation::user();
    $profile = $variant === 'user'
        ? ['name' => (auth()->user()->name ?? null) ?: (auth()->user()->username ?? 'User'), 'meta' => auth()->user()->membership_level ?? '']
        : null;
@endphp

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>{{ $title ?? 'Overview' }} — NP Digital</title>

    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        a.gt_switcher-popup span {
            display: none;
        }
    </style>
</head>

<body class="np-shell">
    <div x-data="{ open: false, collapsed: false }" class="group/sidebar-wrapper flex min-h-svh w-full">
        <div class="panel-shell flex h-dvh min-h-0 w-full overflow-hidden">
            <div x-cloak x-show="open" @click="open = false" class="fixed inset-0 z-30 bg-gray-900/50 md:hidden"></div>

            <x-dashboard.sidebar :nav="$nav" :variant="$variant" :profile="$profile" />

            <div class="relative flex min-h-0 min-w-0 flex-1 flex-col overflow-visible bg-background">
                <x-dashboard.header :variant="$variant">
                    @if ($variant === 'user')
                        <x-slot:actions>
                            <a class="inline-flex h-9 items-center gap-1.5 rounded-md px-2 text-sm transition-colors hover:bg-muted">
                                <div class="gtranslate_wrapper"></div>
                            </a>

                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                <x-dashboard.icon name="circle-user-round" class="h-5 w-5 text-primary" />
                            </span>

                            <a href="{{ route('dashboard.notifications') }}" aria-label="Notifications"
                                class="relative inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors hover:bg-muted">
                                <x-dashboard.icon name="bell" class="h-5 w-5 text-foreground" />
                                <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-destructive"></span>
                            </a>
                        </x-slot:actions>
                    @endif
                </x-dashboard.header>

                <main class="min-h-0 min-w-0 flex-1 overflow-x-auto overflow-y-auto overscroll-contain">
                    <div class="p-3 sm:p-4 lg:p-6">
                        <div class="mx-auto min-w-0 w-full max-w-[1800px]">
                            {{ $slot }}
                        </div>
                    </div>

                    @if ($variant === 'user')
                        <x-dashboard.footer />
                        <div class="h-20 md:hidden" aria-hidden="true"></div>
                    @endif
                </main>
            </div>
        </div>
    </div>

    @if ($variant === 'user')
        <x-dashboard.mobile-nav :nav="$nav" />
        <x-dashboard.support-bubble />
    @endif

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <x-toastify />

    @livewireScripts

    @if ($variant === 'user')
        <script>
            window.gtranslateSettings = {
                "default_language": "en",
                "detect_browser_language": true,
                "wrapper_selector": ".gtranslate_wrapper",
                "flag_size": 24,
                "flag_style": "3d"
            }
        </script>
        <script src="https://cdn.gtranslate.net/widgets/latest/popup.js" defer></script>
    @endif
</body>

</html>
