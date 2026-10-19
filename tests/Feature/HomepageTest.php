<?php

it('shows the public marketing homepage with working account entry points', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Full-service digital marketing services')
        ->assertSee('Where your customer is, there you are')
        ->assertSee('Integrated Digital Marketing Services')
        ->assertSee('Content Strategy')
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee(asset('assets/npdigital-logo.png'))
        ->assertSee(asset('assets/np/home/creative-reel.webm'))
        ->assertSee('https://rsms.me/inter/inter.css')
        ->assertDontSee('Space Grotesk')
        ->assertSee('aria-label="Select language"', false)
        ->assertSee('https://cdn.gtranslate.net/widgets/latest/popup.js')
        ->assertSee('Customer Support');
});

it('keeps the dashboard behind authentication', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
