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

    <x-dashboard.support-modal />
</div>
