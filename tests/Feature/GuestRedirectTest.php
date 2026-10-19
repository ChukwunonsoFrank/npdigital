<?php

declare(strict_types=1);

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('redirects unauthenticated users to login for public pages', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with([
    'find-us' => '/find-us',
    'terms' => '/terms',
    'privacy' => '/privacy',
]);

it('shows the homepage to authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertOk()->assertSee('Full-service digital marketing services');
});
