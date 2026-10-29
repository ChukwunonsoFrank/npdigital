@php
    $supportSettings = \App\Models\PlatformSetting::current();
    $whatsappDigits = preg_replace('/\D+/', '', (string) $supportSettings->whatsapp_support_number);
    $telegramUsername = ltrim((string) $supportSettings->telegram_support_username, '@');
    $whatsappUrl = $whatsappDigits !== '' ? 'https://wa.me/'.$whatsappDigits : null;
    $telegramUrl = $telegramUsername !== '' ? 'https://t.me/'.$telegramUsername : null;
@endphp

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

    <div class="mt-5 flex flex-col gap-2">
        <button type="button" @click="supportModalOpen = false; chatOpen = true"
            class="flex w-full items-center gap-3 rounded-xl border border-border bg-card p-3 text-left shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                <x-dashboard.icon name="message-square" class="h-6 w-6 text-primary" />
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-bold text-foreground">Live Chat</span>
                <span class="block text-xs text-muted-foreground">Chat with our support team</span>
            </span>
        </button>

        @if ($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" @click="supportModalOpen = false"
                class="flex w-full items-center gap-3 rounded-xl border border-border bg-card p-3 text-left shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5">
                <img src="{{ asset('assets/img/whatsapp.png') }}" alt="" width="44" height="44"
                    class="h-11 w-11 shrink-0 rounded-lg">
                <span class="min-w-0">
                    <span class="block text-sm font-bold text-foreground">WhatsApp</span>
                    <span class="block text-xs text-muted-foreground">Chat with us on WhatsApp</span>
                </span>
            </a>
        @endif

        @if ($telegramUrl)
            <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer" @click="supportModalOpen = false"
                class="flex w-full items-center gap-3 rounded-xl border border-border bg-card p-3 text-left shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5">
                <img src="{{ asset('assets/img/telegram.png') }}" alt="" width="44" height="44"
                    class="h-11 w-11 shrink-0 rounded-lg">
                <span class="min-w-0">
                    <span class="block text-sm font-bold text-foreground">Telegram</span>
                    <span class="block text-xs text-muted-foreground">Message us on Telegram</span>
                </span>
            </a>
        @endif
    </div>
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
