<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertStatus(200);
});

test('user dashboard header includes the google translate widget', function () {
    $this->actingAs(User::factory()->create());

    $html = view('components.layouts.dashboard', [
        'slot' => '',
        'attributes' => new \Illuminate\View\ComponentAttributeBag,
    ])->render();

    expect($html)->toContain('gtranslate_wrapper')
        ->and($html)->toContain('cdn.gtranslate.net/widgets/latest/popup.js')
        ->and($html)->toContain('gtranslateSettings');
});
