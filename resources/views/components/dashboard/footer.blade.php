@php
    $address = [
        '5940 S Rainbow Blvd, Ste 400',
        'Las Vegas, NV 89118-2507',
    ];

    $sections = [
        [
            'title' => 'Global',
            'links' => [
                ['label' => 'United States', 'url' => '#'],
                ['label' => 'United Kingdom', 'url' => '#'],
                ['label' => 'India', 'url' => '#'],
                ['label' => 'Hong Kong', 'url' => '#'],
                ['label' => 'Singapore', 'url' => '#'],
                ['label' => 'Germany', 'url' => '#'],
                ['label' => 'Brazil', 'url' => '#'],
                ['label' => 'Australia', 'url' => '#'],
            ],
        ],
        [
            'title' => 'Company',
            'links' => [
                ['label' => 'About', 'url' => route('dashboard.about-us')],
                ['label' => 'Solutions', 'url' => '#'],
                ['label' => 'Reviews', 'url' => '#'],
                ['label' => 'Careers', 'url' => '#'],
                ['label' => 'Contact', 'url' => route('dashboard.contact')],
                ['label' => 'Hire Us', 'url' => '#'],
            ],
        ],
        [
            'title' => 'Resources',
            'links' => [
                ['label' => 'Blog', 'url' => '#'],
                ['label' => 'Case Studies', 'url' => '#'],
                ['label' => 'Ubersuggest', 'url' => 'https://neilpatel.com/ubersuggest/', 'external' => true, 'accent' => true],
                ['label' => 'Neil Patel', 'url' => '#'],
                ['label' => 'Webinars', 'url' => '#'],
            ],
        ],
    ];

    $socials = [
        'facebook' => 'https://www.facebook.com/',
        'instagram' => 'https://www.instagram.com/',
        'x' => 'https://x.com/',
        'linkedin' => 'https://www.linkedin.com/',
        'youtube' => 'https://www.youtube.com/',
    ];

    $socialSvgs = [
        'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37Z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>',
        'x' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M18.9 2H22l-7.3 8.3L23 22h-6.6l-5.2-6.8L5.3 22H2l7.8-8.9L1.5 2h6.8l4.7 6.2L18.9 2Zm-1.2 18h1.8L7.4 3.9H5.5L17.7 20Z"/></svg>',
        'linkedin' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.36V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0Z"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.51A3.02 3.02 0 0 0 .5 6.2C0 8.09 0 12 0 12s0 3.91.5 5.8a3.02 3.02 0 0 0 2.12 2.14c1.88.51 9.38.51 9.38.51s7.5 0 9.38-.51a3.02 3.02 0 0 0 2.12-2.14C24 15.91 24 12 24 12s0-3.91-.5-5.8ZM9.55 15.57V8.43L15.82 12l-6.27 3.57Z"/></svg>',
    ];

    $partners = [
        ['name' => 'Google', 'tier' => 'Premier Partner', 'logo' => 'assets/np/partners/google-premier-partner.jpg'],
        ['name' => 'Meta', 'tier' => 'Business Partner', 'logo' => 'assets/np/partners/meta.svg'],
        ['name' => 'Microsoft', 'tier' => 'Advertising Select Partner', 'logo' => 'assets/np/partners/microsoft.svg', 'full' => true],
        ['name' => 'NMSDC', 'tier' => 'Certified MBE', 'logo' => 'assets/np/partners/nmsdc.png'],
        ['name' => 'Amazon Ads', 'tier' => 'Verified Partner', 'logo' => 'assets/np/partners/amazon.svg'],
    ];
@endphp

<footer class="bg-sidebar text-sidebar-foreground">
    <div class="mx-auto w-full max-w-[1800px] px-6 py-12 sm:px-10">
        <img src="{{ asset('assets/npdigital-logo.png') }}" alt="NP Digital" class="h-auto w-[79px] rounded bg-white px-2 py-1">

        <div class="mt-4 space-y-0.5 text-sm text-sidebar-foreground/70">
            @foreach ($address as $line)
                <p>{{ $line }}</p>
            @endforeach
            <a href="mailto:sales@npdigital.com" class="block hover:text-sidebar-foreground">sales@npdigital.com</a>
        </div>

        <div class="mt-10 border-t border-sidebar-border/60">
            @foreach ($sections as $section)
                <div x-data="{ open: false }" class="border-b border-sidebar-border/60">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-4 py-4 text-left">
                        <span class="text-xs font-bold uppercase tracking-[0.2em] text-sidebar-foreground">{{ $section['title'] }}</span>
                        <span class="text-sidebar-foreground/60">
                            <x-dashboard.icon name="plus" class="h-4 w-4" x-show="!open" />
                            <x-dashboard.icon name="minus" class="h-4 w-4" x-show="open" x-cloak />
                        </span>
                    </button>

                    <ul x-show="open" x-cloak class="flex flex-col gap-3 pb-6">
                        @foreach ($section['links'] as $link)
                            <li>
                                <a href="{{ $link['url'] }}"
                                    @if (!empty($link['external'])) target="_blank" rel="noopener" @endif
                                    @class([
                                        'text-sm',
                                        'font-medium text-sidebar-primary hover:underline' => !empty($link['accent']),
                                        'text-sidebar-foreground/70 hover:text-sidebar-foreground' => empty($link['accent']),
                                    ])>
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="py-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-sidebar-foreground">Social</p>
                <div class="mt-4 flex gap-3">
                    @foreach ($socials as $name => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($name) }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-sidebar-accent text-sidebar-foreground transition-colors hover:bg-sidebar-accent/70">
                            {!! $socialSvgs[$name] !!}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach ($partners as $partner)
                <div @class([
                    'flex items-center gap-3 rounded-lg border border-sidebar-border bg-white/5 p-4 transition-all duration-200 hover:-translate-y-1 hover:border-primary hover:bg-white/10 hover:shadow-lg',
                    'col-span-2 justify-center lg:col-span-1 lg:justify-start' => !empty($partner['full']),
                ])>
                    <span class="flex h-9 w-11 shrink-0 items-center justify-center rounded-md bg-white p-1.5">
                        <img src="{{ asset($partner['logo']) }}" alt="{{ $partner['name'] }}"
                            class="h-full w-full object-contain">
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-sidebar-foreground">{{ $partner['name'] }}</p>
                        <p class="text-[11px] leading-tight text-sidebar-foreground/60">{{ $partner['tier'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-10 border-t border-sidebar-border/60 pt-6 text-center text-xs text-sidebar-foreground/50">© 2026 NP
            Digital. All rights reserved.</p>
    </div>
</footer>
