@props(['heading', 'compact' => false, 'back' => null])

<main @class(['flex min-h-svh flex-col items-center bg-background px-4 py-4 sm:justify-center sm:py-6', 'justify-center' => ! $compact])>
    <div @class(['flex w-full max-w-md items-center justify-between', 'mb-2' => $compact, 'mb-4' => ! $compact])>
        <a href="{{ $back ?? route('home') }}" class="inline-flex h-9 items-center gap-1.5 rounded-md px-3 text-sm font-bold transition-colors hover:bg-muted focus-visible:outline-2 focus-visible:outline-primary">
            <x-dashboard.icon name="arrow-left" class="h-4 w-4 stroke-[3]" /> Back
        </a>
        <div wire:ignore class="gtranslate_wrapper" aria-label="Select language"></div>
    </div>
    <section @class(['auth-card w-full max-w-md rounded-2xl border-border bg-card shadow-xl', 'border p-5 sm:p-7' => $compact, 'auth-card-large space-y-6 border-2 p-8' => ! $compact])>
        <header @class(['text-center', 'mb-4' => $compact, 'space-y-2' => ! $compact])>
            <img src="{{ asset('assets/npdigital-logo.png') }}" alt="NP Digital" @class(['mx-auto h-auto object-contain', 'mb-1 w-[79px] sm:w-[92px]' => $compact, 'w-[92px] sm:w-[105px]' => ! $compact])>
            <h1 @class(['font-bold uppercase tracking-wide text-muted-foreground', 'text-xs' => $compact, 'text-sm' => ! $compact])>{{ $heading }}</h1>
        </header>
        {{ $slot }}
    </section>
</main>
