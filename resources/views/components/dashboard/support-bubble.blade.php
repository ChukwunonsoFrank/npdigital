<div x-data="{ supportModalOpen: false, chatOpen: false }"
    @keydown.escape.window="supportModalOpen = false; chatOpen = false">
    <div class="fixed bottom-[calc(68px+env(safe-area-inset-bottom)+1rem)] right-4 z-40 md:bottom-6 md:right-6">
        <button type="button" @click="supportModalOpen = true" aria-label="Customer Support"
            class="relative inline-flex h-14 w-14 items-center justify-center rounded-full border border-primary/20 bg-primary text-primary-foreground shadow-lg shadow-primary/30 transition-transform hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
            <svg viewBox="0 0 48 48" aria-hidden="true" class="h-9 w-9" fill="none">
                <path
                    d="M20.5 11.5h15.25A6.75 6.75 0 0 1 42.5 18.25v11.5a6.75 6.75 0 0 1-6.75 6.75H34v5.25a1.25 1.25 0 0 1-2.13.88l-6.12-6.13H20.5V29h13.25a2.75 2.75 0 0 0 2.75-2.75v-9A2.75 2.75 0 0 0 33.75 14.5H20.5v-3Z"
                    fill="currentColor" />
                <path
                    d="M6 21.75A6.75 6.75 0 0 1 12.75 15h14.5A6.75 6.75 0 0 1 34 21.75v10.5A6.75 6.75 0 0 1 27.25 39H18l-6.87 6.87A1.25 1.25 0 0 1 9 44.99V38.4a6.75 6.75 0 0 1-3-5.65v-11Z"
                    fill="currentColor" />
            </svg>
        </button>
    </div>

    <div x-cloak x-show="supportModalOpen" x-transition.opacity.duration.200ms class="fixed inset-0 z-50 bg-black/50"
        @click="supportModalOpen = false"></div>

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
