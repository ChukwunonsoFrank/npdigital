@php
    $routeName = request()->route()?->getName() ?? '';
    $variant = str_starts_with($routeName, 'admin.') ? 'admin' : 'user';
    $nav = $variant === 'admin' ? \App\Support\Navigation::admin() : \App\Support\Navigation::user();
    $profile = $variant === 'user'
        ? ['name' => (auth()->user()->name ?? null) ?: (auth()->user()->username ?? 'User'), 'meta' => auth()->user()->membership_level ?? '']
        : null;
    $viewingAsUser = $variant === 'user' && session()->has('view_as_admin_id');
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

<body @class(['np-shell', 'admin-shell' => $variant === 'admin'])>
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
                    @if ($viewingAsUser)
                        <div class="px-3 pt-3 sm:px-4 sm:pt-4 lg:px-6 lg:pt-6">
                            <div class="mx-auto w-full max-w-[1800px]">
                                <div
                                    class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-500/40 bg-emerald-50 px-4 py-3 dark:border-emerald-400/30 dark:bg-emerald-950/40">
                                    <p class="flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                        <x-dashboard.icon name="eye" class="h-4 w-4" />
                                        Viewing as user — read-only admin preview
                                    </p>

                                    <form method="POST" action="{{ route('view-as.exit') }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <x-dashboard.icon name="arrow-left" class="h-4 w-4" />
                                            Back to Admin
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

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
    @fluxScripts

    @if ($viewingAsUser)
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status !== 403) {
                            return;
                        }

                        preventDefault();

                        if (window.showToast) {
                            window.showToast('Read-only preview — actions are disabled while viewing a user.');
                        }
                    });
                });
            });
        </script>
    @endif

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
