<div x-data="{ supportModalOpen: false, chatOpen: false }"
    @keydown.escape.window="supportModalOpen = false; chatOpen = false">
    <div class="space-y-6 animate-fade-in">
        <div>
            <h1 class="font-display text-2xl font-bold text-foreground sm:text-3xl">Welcome back, {{ auth()->user()->username }}!</h1>
            <p class="mt-1 text-sm text-muted-foreground">Here's what's happening today</p>
        </div>

        <div class="flex items-stretch overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div
                class="flex shrink-0 items-center bg-primary px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-primary-foreground">
                Announcements
            </div>
            <div class="relative flex-1 overflow-hidden">
                <div class="flex w-max animate-marquee items-center gap-10 whitespace-nowrap py-3">
                    @foreach (array_merge($announcements, $announcements) as $announcement)
                        <span class="flex items-center gap-2 text-sm text-foreground/80">
                            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>{{ $announcement }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl shadow-sm" style="height: clamp(140px, 25vw, 220px);">
            <img src="{{ asset('assets/np/world-map.jpg') }}" alt="NP Digital global network"
                class="h-full w-full object-cover" loading="eager" decoding="sync" fetchpriority="high">

            <div class="pointer-events-none absolute inset-0">
                @foreach ($mapPoints as [$top, $left, $duration, $delay])
                    <span class="glitter-dot"
                        style="top: {{ $top }}%; left: {{ $left }}%; animation-duration: {{ $duration }}s; animation-delay: {{ $delay }}s;"></span>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-2 shadow-sm sm:p-3">
            <div class="grid grid-cols-6 gap-2">
                @php
                    $tileClass = 'group flex flex-col items-center justify-center gap-1.5 rounded-lg border border-border px-1 py-2.5 text-center transition-colors hover:border-primary/40 hover:bg-primary/5 sm:gap-2 sm:px-1.5 sm:py-3';
                @endphp
                @foreach ($services as $service)
                    @if (!empty($service['modal']))
                        <button type="button" @click="supportModalOpen = true" class="{{ $tileClass }}">
                            <x-dashboard.icon :name="$service['icon']" class="h-6 w-6 text-primary" />
                            <span class="w-full truncate text-[10px] font-medium text-foreground sm:text-xs">{{ $service['label'] }}</span>
                        </button>
                    @else
                        <a href="{{ $service['url'] }}" wire:navigate class="{{ $tileClass }}">
                            <x-dashboard.icon :name="$service['icon']" class="h-6 w-6 text-primary" />
                            <span class="w-full truncate text-[10px] font-medium text-foreground sm:text-xs">{{ $service['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm sm:p-10">
            <p class="text-center text-xs font-bold uppercase tracking-[0.2em] text-primary">Why Us?</p>
            <h2 class="mt-2 text-center font-display text-2xl font-bold text-foreground sm:text-3xl">Three Reasons to Choose Us</h2>

            <div class="relative mt-10 grid grid-cols-3 gap-3 sm:mt-12 sm:gap-8">
                <div class="pointer-events-none absolute left-[16.666%] right-[16.666%] top-8 h-px bg-border sm:top-10"></div>

                @foreach ($reasons as $reason)
                    <div class="relative flex flex-col items-center text-center">
                        <span
                            class="flex h-16 w-16 items-center justify-center rounded-full border border-primary/40 bg-card sm:h-20 sm:w-20">
                            <x-dashboard.icon :name="$reason['icon']" class="h-6 w-6 text-primary sm:h-7 sm:w-7" />
                        </span>
                        <h3 class="mt-4 text-sm font-bold text-foreground sm:mt-6 sm:text-base">{{ $reason['title'] }}</h3>
                        <p class="mt-2 hidden max-w-xs text-sm text-muted-foreground sm:block">{{ $reason['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-xl shadow-sm"
            style="background: linear-gradient(120deg, #1f9fd8 0%, #2bc4c9 100%);">
            <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:gap-10 sm:p-10">
                <div class="w-full overflow-hidden rounded-lg border border-white/20 shadow-lg sm:w-1/2 sm:max-w-md">
                    <iframe src="{{ $findUs['embedUrl'] }}" title="NP Digital office location" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade" class="h-56 w-full sm:h-64"></iframe>
                </div>
                <div class="text-white sm:flex-1">
                    <h3 class="font-display text-2xl font-bold sm:text-3xl">{{ $findUs['title'] }}</h3>
                    <p class="mt-2 max-w-md text-sm text-white/80">{{ $findUs['description'] }}</p>
                    <a href="{{ $findUs['mapUrl'] }}" target="_blank" rel="noopener"
                        class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-white underline underline-offset-4">
                        <x-dashboard.icon name="map-pin" class="h-4 w-4" />
                        Tap to open map
                    </a>
                </div>
            </div>
        </div>
    </div>

    <x-dashboard.support-modal />
</div>
