@props([
    'brand' => null,
    'size' => 'md',
])

@php
    $logo = \App\Support\ProductBrand::logo($brand);
    $label = \App\Support\ProductBrand::label($brand);
@endphp

@if ($logo)
    <span
        {{ $attributes->class([
            'absolute right-1.5 top-1.5 z-10 inline-flex items-center justify-center rounded-md bg-white/95 px-1.5 py-0.5 shadow-sm ring-1 ring-black/10',
        ]) }}>
        <img src="{{ $logo }}" alt="{{ $label }} logo" loading="lazy"
            @class([
                'w-auto object-contain',
                'h-3 max-w-10' => $size === 'sm',
                'h-4 max-w-14' => $size !== 'sm',
            ])>
    </span>
@endif
