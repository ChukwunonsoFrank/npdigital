@php
    $paragraphs = [
        "Growth isn't about throwing dozens of experiments at the wall, hoping one sticks. It's not about hiring an army of data scientists, product managers, and engineers to chase the next breakthrough. That's like trying to catch lightning in a bottle. It's possible, but unlikely and inefficient.",
        'For 20+ years, NP Digital has helped businesses scale by focusing on what truly matters: understanding your unique use case, aligning with your ideal customer profile (ICP), and executing proven acquisition strategies that convert prospects into SQLs.',
        'We are one of only six agencies in the U.S. accredited as a Google Certified Marketing Platform Partner across Google Analytics, Tag Manager, and Surveys, in addition to being a Google Ads Premier Partner.',
        'By intentionally remaining boutique, we ensure senior-level expertise on every project, tighter collaboration, and strategies for your exact business needs.',
    ];

    $trustPoints = [
        ['title' => 'Acquisition-Focused Growth', 'body' => 'At NP Digital, we attract and convert the right prospects. We ensure every initiative aligns with your business goals.'],
        ['title' => 'A Natural Extension of Your Team', 'body' => 'We embed ourselves into your team. Through tight-knit collaboration, we work toward shared objectives with full transparency.'],
        ['title' => 'Custom Strategies, No Templates', 'body' => "No cookie-cutter tactics. Every marketing system we build is designed for your business's unique needs and industry dynamics."],
        ['title' => 'Forward-Thinking & Data-Driven', 'body' => "We don't just optimize for today; we adapt to the latest technological advances in our industry."],
        ['title' => '20+ Years of Industry-Leading Experience', 'body' => "With two decades of proven success, we've helped global brands and fast-growing companies scale their acquisition efforts."],
    ];

    $awards = [
        ['logo' => 'assets/np/about/award-cambridge.png', 'alt' => "Cambridge Who's Who"],
        ['logo' => 'assets/np/about/award-patriot.png', 'alt' => 'Employer Support of the Guard and Reserve Patriot Award'],
        ['logo' => 'assets/np/about/award-njbiz-business-of-the-year.png', 'alt' => 'NJBIZ Business of the Year'],
        ['logo' => 'assets/np/about/award-njbiz-best-places-to-work.png', 'alt' => 'NJBIZ Best Places to Work'],
        ['logo' => 'assets/np/about/award-50-fastest-growing.png', 'alt' => 'NJBIZ 50 Fastest Growing Companies'],
        ['logo' => 'assets/np/about/award-ama.png', 'alt' => 'American Marketing Association'],
        ['logo' => 'assets/np/about/award-cfo-of-the-year.png', 'alt' => 'NJBIZ CFO of the Year Awards'],
    ];

    $articles = [
        ['brand' => 'CMSWIRE', 'brandClass' => 'text-[#2bab8b] tracking-widest', 'title' => '4 Marketing Lessons Learned Over 4 Decades'],
        ['brand' => 'MT', 'brandClass' => 'text-primary tracking-tight', 'title' => '6 Steps to Find the Right Acquisition Channels for Scalable Growth'],
    ];

    $services = [
        [
            'icon' => 'search',
            'title' => 'SEO & GEO',
            'items' => ['SEO services', 'AI Search (GEO)', 'National SEO', 'eCommerce SEO Services', 'Local SEO'],
            'cta' => 'All SEO & GEO Services',
        ],
        [
            'icon' => 'monitor',
            'title' => 'Paid Media',
            'items' => ['Paid Media (SEM/PPC)', 'Bing Ads', 'Google Ads', 'Programmatic Buying', 'Social Media Advertising'],
            'cta' => 'All Paid Media Services',
        ],
        [
            'icon' => 'palette',
            'title' => 'Web Development',
            'items' => ['Web Design and Development', 'eCommerce Integration', 'Conversion Rate Optimization', 'UI & UX'],
            'cta' => 'All Web Development Services',
        ],
        [
            'icon' => 'megaphone',
            'title' => 'Content & PR',
            'items' => ['Content Auditing', 'Content Marketing', 'Digital PR', 'Guest Posting'],
            'cta' => 'All Content & PR Services',
        ],
        [
            'icon' => 'star',
            'title' => 'Branding & Creative',
            'items' => ['Brand Strategy and Design', 'Email Marketing', 'Video Marketing', 'Creative Marketing', 'Graphic Design'],
            'cta' => 'All Branding & Creative Services',
        ],
        [
            'icon' => 'shopping-cart',
            'title' => 'Amazon',
            'items' => ['Amazon Marketing', 'Amazon Seller Central', 'Amazon Vendor Central', 'Demand Side Platform (DSP)'],
            'cta' => 'All Amazon Services',
        ],
        [
            'icon' => 'shopping-cart',
            'title' => 'Walmart',
            'items' => ['Walmart Marketing', 'Walmart Seller Central', 'Walmart Vendor Central', 'Demand Side Platform (DSP)'],
            'cta' => 'All Walmart Services',
        ],
        [
            'icon' => 'shopping-cart',
            'title' => 'eBay',
            'items' => ['eBay Marketing', 'eBay Seller Central', 'eBay Vendor Central', 'Demand Side Platform (DSP)'],
            'cta' => 'All eBay Services',
        ],
    ];
@endphp

<div x-data="{ tab: 'about', supportModalOpen: false, chatOpen: false }"
    @keydown.escape.window="supportModalOpen = false; chatOpen = false">
    <div class="animate-fade-in">
        {{-- Tabs span the full width; content below stays inset --}}
        <div class="-mx-3 border-b border-border sm:-mx-4 lg:-mx-6">
            <div class="flex gap-6 px-4 pt-4 sm:px-6">
                <button type="button" @click="tab = 'about'"
                    class="border-b-2 pb-3 text-sm font-bold uppercase tracking-wider transition-colors"
                    :class="tab === 'about' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">About</button>
                <button type="button" @click="tab = 'services'"
                    class="border-b-2 pb-3 text-sm font-bold uppercase tracking-wider transition-colors"
                    :class="tab === 'services' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">Services</button>
            </div>
        </div>

        <div x-show="tab === 'about'" class="px-4 pb-4 sm:px-6">
            <div class="mx-auto w-full max-w-5xl">
                <div class="pb-2 pt-6">
                    <p class="text-right text-sm text-foreground/70">
                        <a href="{{ route('dashboard') }}" wire:navigate class="font-semibold text-primary underline underline-offset-2 hover:opacity-80">Home</a>
                        <span class="mx-1.5 text-muted-foreground">/</span>
                        <span>About Us</span>
                    </p>
                </div>
            </div>

            <section class="pb-10 pt-6">
                <div class="mx-auto w-full max-w-5xl">
                    <h1 class="mb-10 font-display text-4xl font-bold leading-tight text-primary md:text-6xl">About Our Growth Agency</h1>
                    <h2 class="mb-6 font-display text-xl font-bold text-foreground md:text-2xl">Strategic Growth that Drives Sales-Qualified Leads (SQLs)</h2>

                    <div class="mb-8 overflow-hidden rounded-lg">
                        <img src="{{ asset('assets/np/about/business-meeting.jpg') }}" alt="Growth strategy meeting" class="block h-auto w-full" loading="lazy">
                    </div>

                    <div class="space-y-5 text-base leading-relaxed text-foreground/80 md:text-[17px]">
                        @foreach ($paragraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto w-full max-w-5xl">
                    <h2 class="mb-6 font-display text-xl font-bold text-foreground md:text-2xl">Why Marketing Leaders Trust NP Digital</h2>

                    <ul class="list-disc space-y-4 pl-6 text-base leading-relaxed text-foreground/80 marker:text-foreground md:text-[17px]">
                        @foreach ($trustPoints as $point)
                            <li><span class="font-bold text-foreground">{{ $point['title'] }}</span> <span>&mdash; {{ $point['body'] }}</span></li>
                        @endforeach
                    </ul>

                    <div class="mt-10">
                        <h3 class="mb-3 font-display text-xl font-bold text-foreground md:text-2xl">Let's Chat About Accelerating Your Customer Acquisition</h3>
                        <button type="button" @click="supportModalOpen = true" class="text-base font-bold text-primary underline underline-offset-4 hover:opacity-80 md:text-[17px]">Schedule your strategy call.</button>
                    </div>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto w-full max-w-5xl">
                    <h2 class="mb-8 font-display text-xl font-bold text-foreground md:text-2xl">Meet Our Team</h2>

                    <button type="button" @click="supportModalOpen = true" aria-label="Schedule a call to meet the team" class="block w-full overflow-hidden rounded-lg p-0 focus:outline-none focus:ring-2 focus:ring-primary">
                        <img src="{{ asset('assets/np/about/team-grid-photo.png') }}" alt="Meet our team" class="block h-auto w-full" loading="lazy">
                    </button>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto w-full max-w-6xl">
                    <h2 class="mb-10 text-center font-display text-2xl font-bold text-foreground md:text-3xl">Some of our awards</h2>

                    <div class="relative rounded-lg bg-muted/60 p-2 sm:p-3" x-data="{ slide: 0, total: {{ count($awards) }} }">
                        <div class="min-w-0 overflow-hidden">
                            <div class="flex transition-transform duration-500 ease-out" x-bind:style="`transform: translateX(-${slide * 100}%)`">
                                @foreach ($awards as $award)
                                    <div class="w-full shrink-0 p-1 sm:p-2">
                                        <div class="flex aspect-[16/9] w-full items-center justify-center overflow-hidden rounded-md bg-card p-2 sm:p-3">
                                            <img src="{{ asset($award['logo']) }}" alt="{{ $award['alt'] }}" class="block h-full w-full object-contain object-center" loading="lazy">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <button type="button" aria-label="Previous awards" @click="slide = slide > 0 ? slide - 1 : total - 1"
                            class="absolute left-1 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-border/60 bg-card/90 text-foreground/70 backdrop-blur transition-colors hover:border-primary hover:text-primary sm:h-10 sm:w-10">
                            <x-dashboard.icon name="chevron-left" class="h-5 w-5" />
                        </button>
                        <button type="button" aria-label="Next awards" @click="slide = slide < total - 1 ? slide + 1 : 0"
                            class="absolute right-1 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-border/60 bg-card/90 text-foreground/70 backdrop-blur transition-colors hover:border-primary hover:text-primary sm:h-10 sm:w-10">
                            <x-dashboard.icon name="chevron-right" class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto w-full max-w-5xl">
                    <h2 class="mb-6 font-display text-xl font-bold text-foreground md:text-2xl">Recent Articles &amp; Thought Leadership</h2>

                    <div class="space-y-5">
                        @foreach ($articles as $article)
                            <article class="rounded-lg border border-border/60 bg-card p-6">
                                <p class="mb-3 font-display text-xl font-bold {{ $article['brandClass'] }}">{{ $article['brand'] }}</p>
                                <h3 class="mb-3 text-base text-foreground md:text-[17px]">{{ $article['title'] }}</h3>
                                <button type="button" class="text-sm font-bold text-primary underline underline-offset-4 hover:opacity-80">Read full article</button>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <button type="button" class="font-bold text-primary underline underline-offset-4 hover:opacity-80">See all articles</button>
                    </div>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto w-full max-w-5xl">
                    <h2 class="mb-5 font-display text-xl font-bold text-foreground md:text-2xl">Giving Back</h2>
                    <p class="mb-10 text-base leading-relaxed text-foreground/80 md:text-[17px]">NP Digital is committed to the improvement of our community and we're always looking for new ways to help. Whether it be sponsoring a local charity event or providing pro-bono marketing services, we do what we can to make a difference.</p>

                    <div class="overflow-hidden rounded-lg bg-muted/40">
                        <div class="border-b border-border/40 px-6 py-6 text-center">
                            <h3 class="font-display text-xl font-bold text-foreground md:text-2xl">We support</h3>
                        </div>
                        <div class="px-6 py-10">
                            <div class="grid grid-cols-1 items-center justify-items-center gap-8 text-foreground/80 md:grid-cols-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-foreground/90 font-display text-lg font-bold text-background">B</div>
                                    <span class="font-semibold">Big Brothers<br>Big Sisters.</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-primary font-display text-lg font-bold text-primary-foreground">&hearts;</div>
                                    <span class="font-semibold">One Warm Coat&reg;</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-display text-lg italic text-primary">Mary's Place</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-10 text-base leading-relaxed text-foreground/80 md:text-[17px]">We do anything we can to support local businesses and charitable organizations. We believe it's not enough to just be part of your community &mdash; we all have to take action to make it a better place for everyone to live and work.</p>
                </div>
            </section>

            <section class="py-14">
                <div class="mx-auto w-full max-w-5xl text-center">
                    <button type="button" @click="supportModalOpen = true" class="inline-flex h-11 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-8 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        Schedule a FREE Strategy Meeting
                        <x-dashboard.icon name="arrow-up-right" class="h-4 w-4" />
                    </button>
                </div>
            </section>
        </div>

        <div x-cloak x-show="tab === 'services'" class="px-4 pb-4 pt-6 sm:px-6">
            <div class="mx-auto w-full max-w-5xl space-y-8">
                <section>
                    <h2 class="mb-2 font-display text-2xl font-bold text-foreground">Our Services</h2>
                    <p class="text-sm text-muted-foreground">Comprehensive digital solutions to grow your business.</p>
                </section>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($services as $service)
                        <div class="rounded-xl border border-border bg-card p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary">
                                    <x-dashboard.icon :name="$service['icon']" class="h-4 w-4 text-primary-foreground" />
                                </div>
                                <h3 class="font-display text-base font-bold text-primary">{{ $service['title'] }}</h3>
                            </div>

                            <ul class="mb-4 space-y-1.5">
                                @foreach ($service['items'] as $item)
                                    <li class="flex items-start gap-1.5 text-sm text-muted-foreground">
                                        <span class="mt-1.5 shrink-0">&middot;</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <button type="button" class="flex items-center gap-1 text-sm font-bold text-primary hover:underline">
                                {{ $service['cta'] }}
                                <x-dashboard.icon name="arrow-up-right" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    @endforeach
                </div>

                <section class="rounded-xl border border-primary/20 bg-primary/5 p-8 text-center">
                    <h3 class="mb-2 font-display text-xl font-bold text-foreground">Ready to get started?</h3>
                    <p class="mb-4 text-sm text-muted-foreground">Contact our team to discuss how we can help your business grow.</p>
                    <button type="button" @click="supportModalOpen = true" class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-primary px-8 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                        Talk To Our Team
                        <x-dashboard.icon name="arrow-up-right" class="h-4 w-4" />
                    </button>
                </section>
            </div>
        </div>
    </div>

    <div x-cloak x-show="supportModalOpen" x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 bg-black/50" @click="supportModalOpen = false"></div>

    <div x-cloak x-show="supportModalOpen" role="dialog" aria-modal="true" aria-label="Support Services"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed left-1/2 top-1/2 z-50 w-full -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-card p-5 shadow-xl sm:max-w-md">
        <div class="relative flex items-center justify-center">
            <h2 class="font-display text-lg font-bold text-foreground">Support Services</h2>
            <button type="button" @click="supportModalOpen = false" aria-label="Close"
                class="absolute right-0 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground">
                <x-dashboard.icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <button type="button" @click="supportModalOpen = false; chatOpen = true"
            class="mt-5 flex w-full items-center gap-3 rounded-xl border border-border bg-card p-3 text-left shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                <x-dashboard.icon name="message-square" class="h-6 w-6 text-primary" />
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-bold text-foreground">Live Chat</span>
                <span class="block text-xs text-muted-foreground">Chat with our support team</span>
            </span>
        </button>
    </div>

    <template x-if="chatOpen">
        <div class="fixed inset-0 z-[60] flex flex-col bg-background">
            <div class="flex items-center gap-4 border-b border-border px-4 py-3">
                <h2 class="flex-1 font-display text-base font-bold text-foreground">Live Chat</h2>
                <button type="button" @click="chatOpen = false" aria-label="Close chat"
                    class="text-muted-foreground transition-colors hover:text-foreground">
                    <x-dashboard.icon name="x" class="h-5 w-5" />
                </button>
            </div>
            <iframe frameborder="0" title="Live Chat" class="w-full flex-1"
                src="https://code.jivosite.com/chatpage/itW0erCyp0"></iframe>
        </div>
    </template>
</div>
