<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\ComponentAttributeBag;

it('renders the google translate widget in the app header instead of the gtranslate flag widget', function () {
    $html = view('components.layouts.app', [
        'slot' => '',
        'attributes' => new ComponentAttributeBag,
    ])->render();

    expect($html)->toContain('google_translate_element')
        ->and($html)->toContain('translate.google.com/translate_a/element.js')
        ->and($html)->not->toContain('gtranslate');
});

it('renders the google translate widget in the landing page header instead of the gtranslate flag widget', function () {
    Route::get('/findus', fn () => '')->name('findus');
    Route::get('/terms', fn () => '')->name('terms');
    Route::get('/privacy', fn () => '')->name('privacy');
    Route::getRoutes()->refreshNameLookups();

    $html = view('components.layouts.landing-page', [
        'slot' => '',
        'attributes' => new ComponentAttributeBag,
    ])->render();

    expect($html)->toContain('google-translate-element')
        ->and($html)->toContain('translate.google.com/translate_a/element.js')
        ->and($html)->not->toContain('gtranslate');
});
