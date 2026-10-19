<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>NP Digital — Full-service Digital Marketing</title>
    <meta name="description" content="From ideation to execution, unlock a cohesive strategy with a one-stop agency for all your digital marketing needs and goals.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .homepage { --color-accent: hsl(165 60% 42%); --color-accent-foreground: white; }
        .home-container { width: 100%; max-width: 1400px; margin-inline: auto; padding-inline: 2rem; }
        .home-hero { background: radial-gradient(ellipse at top right, hsl(195 90% 40% / .15), transparent 60%), linear-gradient(135deg, #0e121b 0%, #192234 50%, #1f3d47 100%); }
        .homepage a:focus-visible, .homepage button:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 4px; }
        a.gt_switcher-popup span { display: none; }
        a.gt_switcher-popup { padding: 0 !important; }
        a.gt_switcher-popup img { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; box-shadow: 0 1px 3px #0002; }
        .homepage .gt_black_overlay { inset: 0; width: 100%; height: 100dvh; background: rgb(0 0 0 / .75); opacity: 1; }
        .homepage .gt_white_content { top: 50%; left: 50%; margin: 0; transform: translate(-50%, -50%); max-width: calc(100vw - 2rem); max-height: calc(100dvh - 2rem); border-radius: .75rem; box-shadow: 0 25px 50px -12px #0008; overscroll-behavior: contain; }
        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        @media (max-width: 639px) {
            .home-container { padding-inline: 1.25rem; }
            .homepage .gt_white_content .gt_languages { display: block; max-height: none; }
            .homepage .gt_white_content a { white-space: normal; line-height: 1.4; }
        }
    </style>
</head>
<body class="np-shell homepage bg-background text-foreground antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-20 focus:left-4 focus:z-50 focus:rounded focus:bg-card focus:p-3">Skip to content</a>
    <nav aria-label="Main navigation" class="fixed inset-x-0 top-0 z-50 bg-card/95 shadow-sm">
        <div class="home-container flex h-16 items-center justify-between gap-3">
            <a href="{{ route('home') }}" aria-label="NP Digital home" class="min-w-0 shrink">
                <img src="{{ asset('assets/npdigital-logo.png') }}" alt="NP Digital" class="h-auto w-[66px] max-w-full object-contain sm:w-[79px] lg:w-[92px]">
            </a>
            <div class="flex shrink-0 items-center gap-1 sm:gap-3">
                <div class="gtranslate_wrapper" aria-label="Select language"></div>
                <a href="{{ route('login') }}" class="rounded-md px-3 py-2.5 text-sm font-medium transition-colors hover:bg-secondary sm:px-4">Sign In</a>
                <a href="{{ route('register') }}" class="rounded-md bg-primary px-3 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 sm:px-4">Get Started</a>
            </div>
        </div>
    </nav>

    <main id="main-content">
        <section class="home-hero relative overflow-hidden pt-32 pb-24">
            <div class="home-container grid items-center gap-12 md:grid-cols-2">
                <div>
                    <p class="mb-4 text-sm font-semibold tracking-widest text-primary uppercase">Integrated Services</p>
                    <h1 class="font-display mb-6 text-4xl leading-tight font-bold text-primary-foreground md:text-5xl lg:text-6xl">Full-service digital marketing services</h1>
                    <p class="mb-8 max-w-lg text-lg text-primary-foreground/70">From ideation to execution, unlock a cohesive strategy with a one-stop agency for all your digital marketing needs and goals.</p>
                    <a href="#contact" class="inline-flex h-11 items-center justify-center rounded-full bg-primary px-8 text-sm font-bold tracking-wider text-primary-foreground uppercase transition-colors hover:bg-primary/90">Let's Talk Impact</a>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl bg-sidebar shadow-2xl">
                    <video src="{{ asset('assets/np/home/creative-reel.webm') }}" title="NP Digital creative showcase" aria-label="NP Digital creative showcase" controls playsinline preload="none" class="absolute inset-0 h-full w-full object-cover">
                        Your browser does not support video. <a href="{{ asset('assets/np/home/creative-reel.webm') }}">Download our creative showcase</a>.
                    </video>
                    <div class="pointer-events-none absolute top-3 left-3 rounded-md bg-card/90 px-3 py-2 shadow-lg sm:top-4 sm:left-4">
                        <img src="{{ asset('assets/npdigital-logo.png') }}" alt="NP Digital" class="h-auto w-[46px] sm:w-[53px]">
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-background py-24" aria-labelledby="journey-heading">
            <div class="home-container text-center">
                <h2 id="journey-heading" class="font-display mb-4 text-3xl font-bold">Where your customer is, there you are</h2>
                <p class="mx-auto mb-16 max-w-2xl text-muted-foreground">We specialize in a synergistic approach, offering a range of full-service digital marketing solutions and integrated agency services that span your entire customer journey.</p>
                <div class="grid grid-cols-2 gap-8 md:grid-cols-5">
                    @foreach ([
                        ['Attract', 'Attract new audiences and boost brand awareness.', 'magnet'],
                        ['Engage', 'Draw prospects in with tailored messaging and ads.', 'radio'],
                        ['Validate', 'Showcase your reputation and social proof.', 'circle-check'],
                        ['Convert', 'Close deals and generate revenue and growth.', 'repeat'],
                        ['Re-Engage & Upsell', 'Create advocacy and lifetime value.', 'dollar-sign'],
                    ] as [$title, $description, $icon])
                        <div class="flex flex-col items-center">
                            <div class="relative mb-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-secondary text-primary-foreground">
                                    @if ($icon === 'magnet')
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 15 4 4M5 8l4 4"/><path d="M2.35 10.65a1.2 1.2 0 0 0 0 1.7l2.3 2.3a1.2 1.2 0 0 0 1.7 0L12.38 8.6a3 3 0 0 1 4.24 4.24l-6.03 6.03a1.2 1.2 0 0 0 0 1.7l2.3 2.3a1.2 1.2 0 0 0 1.7 0l6.36-6.37A9 9 0 0 0 8.22 3.78z"/></svg>
                                    @elseif ($icon === 'radio')
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M16.25 7.76a6 6 0 0 1 0 8.48M19.08 4.93a10 10 0 0 1 0 14.14M4.93 19.07a10 10 0 0 1 0-14.14M7.75 16.24a6 6 0 0 1 0-8.48"/><circle cx="12" cy="12" r="2"/></svg>
                                    @elseif ($icon === 'repeat')
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m17 2 4 4-4 4M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                                    @else
                                        <x-dashboard.icon :name="$icon" class="h-7 w-7" />
                                    @endif
                                </div>
                                <span class="absolute -right-1 -bottom-1 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">{{ $loop->iteration }}</span>
                            </div>
                            <h3 class="font-display mb-2 font-bold">{{ $title }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="about" class="bg-sidebar py-24 text-sidebar-foreground" aria-label="Your partner in growth">
            <div class="home-container grid items-center gap-12 md:grid-cols-2">
                <div class="relative">
                    <img src="{{ asset('assets/np/home/business-meeting.jpg') }}" alt="Business meeting" loading="lazy" width="800" height="640" class="w-full rounded-xl shadow-2xl">
                    <img src="{{ asset('assets/np/home/cac-card.png') }}" alt="Customer Acquisition Cost $1.80" loading="lazy" class="absolute right-6 bottom-6 h-12 rounded-2xl object-contain md:h-14">
                </div>
                <div class="space-y-10">
                    @foreach ([
                        ['Integrated Digital Marketing Solutions', 'We are a one-stop shop for digital marketing, offering integrated solutions from start to finish that support your entire customer journey.', 'filter'],
                        ['Deep Expertise Across Marketing Disciplines', 'Get access to a purpose-led team of problem-solvers who possess the knowledge and experience necessary to deliver extraordinary results.', 'users'],
                        ['Discover Your Partner in Growth', "For more than two decades, we've unlocked our clients’ growth potential through effective digital marketing strategies and cutting-edge technology.", 'wifi'],
                    ] as [$title, $description, $icon])
                        <div class="flex gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 border-primary text-primary"><x-dashboard.icon :name="$icon" class="h-5 w-5" /></div>
                            <div>
                                <h3 class="font-display mb-2 text-xl font-bold">{{ $title }}</h3>
                                <p class="text-sm leading-relaxed text-sidebar-foreground/70">{{ $description }}</p>
                            </div>
                        </div>
                    @endforeach
                    <a href="#contact" class="inline-flex min-h-10 items-center justify-center border border-accent bg-background px-8 py-2 text-sm font-bold tracking-widest text-accent uppercase transition-colors hover:bg-accent/10">Let's Work Together</a>
                </div>
            </div>
        </section>

        <section id="services" class="bg-background py-24" aria-labelledby="services-heading">
            <div class="home-container">
                <h2 id="services-heading" class="font-display mb-12 text-center text-3xl font-bold">Integrated Digital Marketing Services</h2>
                <div class="mx-auto grid max-w-5xl gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (['SEO', 'Paid Media', 'Amazon Marketing', 'Social Media', 'Web Design & Development', 'Digital PR', 'Email Marketing', 'Creative Services', 'Video Marketing', 'Conversion Rate Optimization', 'Influencer Marketing', 'Link Strategy', 'Content Strategy'] as $service)
                        <a href="#contact" @class(['group relative flex h-36 items-center justify-center overflow-hidden rounded-lg px-3 text-center', 'sm:col-start-1 lg:col-start-2' => $loop->last])>
                            <img src="{{ asset('assets/np/home/service-bg-'.($loop->index % 4 + 1).'.jpg') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                            <div class="absolute inset-0 bg-accent/70 transition-colors group-hover:bg-accent/80"></div>
                            <h3 class="font-display relative z-10 text-lg font-bold text-accent-foreground">{{ $service }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer id="contact" class="bg-sidebar pt-16 pb-8 text-sidebar-foreground">
        <div class="home-container">
            <div class="mb-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <img src="{{ asset('assets/npdigital-logo.png') }}" alt="NP Digital" class="mb-4 h-auto w-[73px] rounded bg-white px-2 py-1 sm:w-[79px]">
                    <p class="text-sm leading-relaxed text-sidebar-foreground/60">NP Digital is a globally acclaimed digital marketing agency that helps businesses fulfill their potential through integrated digital marketing solutions.</p>
                </div>
                <div>
                    <h2 class="font-display mb-4 text-lg font-bold">Where to</h2>
                    <ul class="space-y-2 text-sm text-sidebar-foreground/60">
                        @foreach (['About' => '#about', 'Services' => '#services', 'Case Studies' => '#contact', 'Blog' => '#contact', 'Whitepapers' => '#contact', 'Careers' => '#contact', 'Locations' => '#contact', 'Work With Us' => '#contact'] as $label => $href)
                            <li><a href="{{ $href }}" class="transition-colors hover:text-sidebar-primary">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h2 class="font-display mb-4 text-lg font-bold">Get in Touch</h2>
                    <p class="mb-4 text-sm leading-relaxed text-sidebar-foreground/60">Ready to talk? We'd love to hear from you. Email us or chat with our support team.</p>
                    <a href="mailto:support@npdigital.work" class="text-sm text-primary hover:underline">support@npdigital.work</a>
                </div>
                <div>
                    <h2 class="font-display mb-4 text-lg font-bold">Newsletter</h2>
                    <p id="newsletter-help" class="mb-4 text-sm text-sidebar-foreground/60">Subscribe to our weekly newsletter today! Submit opens your email app to request a subscription.</p>
                    <form action="mailto:support@npdigital.work?subject=Newsletter%20subscription" method="get">
                        <input type="hidden" name="subject" value="Newsletter subscription">
                        <label for="newsletter-email" class="sr-only">Email address</label>
                        <input id="newsletter-email" name="body" type="email" required autocomplete="email" aria-describedby="newsletter-help" placeholder="Enter your email address here" class="mb-3 w-full rounded border border-sidebar-border bg-transparent px-3 py-2 text-sm text-sidebar-foreground placeholder:text-sidebar-foreground/40 focus:border-sidebar-primary focus:outline-none">
                        <button type="submit" class="rounded bg-primary px-3 py-2 text-sm font-bold tracking-wider text-primary-foreground uppercase transition-colors hover:bg-primary/90">Submit</button>
                    </form>
                </div>
            </div>
            <div class="border-t border-sidebar-border pt-6 text-center"><p class="text-xs text-sidebar-foreground/40">© {{ date('Y') }} NP Digital. All rights reserved.</p></div>
        </div>
    </footer>

    <x-dashboard.support-bubble />
    <script>
        window.gtranslateSettings = {
            default_language: 'en', detect_browser_language: true,
            wrapper_selector: '.gtranslate_wrapper', flag_size: 24, flag_style: '3d'
        };
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/popup.js" defer></script>
    @livewireScripts
</body>
</html>
