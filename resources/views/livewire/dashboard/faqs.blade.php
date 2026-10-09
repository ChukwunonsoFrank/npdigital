<div class="animate-fade-in">
    <div class="mx-auto w-full max-w-3xl space-y-6">
        <div class="flex items-center gap-3">
            <x-dashboard.icon name="circle-help" class="h-7 w-7 text-primary" />
            <h1 class="font-display text-2xl font-bold">FAQ</h1>
        </div>

        <div class="space-y-2" x-data="{ open: null }">
            @foreach ($faqs as $index => $faq)
                <div wire:key="faq-{{ $index }}" class="rounded-lg border border-border bg-card px-4">
                    <h3 class="flex">
                        <button type="button" @click="open = open === {{ $index }} ? null : {{ $index }}"
                            class="flex flex-1 items-center justify-between py-4 text-left text-sm font-semibold transition-all hover:underline sm:text-base">
                            {{ $faq['question'] }}
                            <x-dashboard.icon name="chevron-down"
                                class="h-4 w-4 shrink-0 transition-transform duration-200"
                                x-bind:class="open === {{ $index }} ? 'rotate-180' : ''" />
                        </button>
                    </h3>
                    <div x-show="open === {{ $index }}" x-cloak x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="overflow-hidden text-sm transition-all">
                        @if (is_array($faq['answer']))
                            <ul class="space-y-1.5 pb-4 leading-relaxed text-muted-foreground">
                                @foreach ($faq['answer'] as $item)
                                    <li class="flex gap-2">
                                        <span class="mt-px shrink-0 text-foreground/40">&bull;</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="pb-4 leading-relaxed text-muted-foreground">{{ $faq['answer'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-green-600/30 bg-green-50 p-4 text-center dark:border-green-500/30 dark:bg-green-500/10">
            <p class="text-sm text-green-800 dark:text-green-300"><strong>Notice:</strong> For further explanation, please click "Support" on the platform to contact our online customer service.</p>
        </div>
    </div>
</div>
