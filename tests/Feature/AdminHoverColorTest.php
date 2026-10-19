<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the requested hover background color throughout the admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard.users'))
        ->assertOk()
        ->assertSee('admin-shell');

    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('.admin-shell .hover\:bg-accent:hover')
        ->toContain('background-color: #2bab8b;');
});
