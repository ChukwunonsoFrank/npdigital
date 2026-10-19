<?php

use App\Livewire\Dashboard\Certificate;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('certificate page shows an empty state when no licenses are uploaded', function () {
    Livewire::test(Certificate::class)
        ->assertSee('No business licenses available yet');
});

test('authenticated users can view all uploaded business licenses', function () {
    PlatformSetting::factory()->create([
        'id' => 1,
        'business_licenses' => ['business-licenses/first.jpg', 'business-licenses/second.png'],
    ]);

    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.certificate'))
        ->assertOk()
        ->assertSee(route('storage.file', 'business-licenses/first.jpg'))
        ->assertSee(route('storage.file', 'business-licenses/second.png'))
        ->assertSee('alt="Business license 2"', false)
        ->assertDontSee('No business licenses available yet');
});
