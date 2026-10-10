<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function signIn(): mixed
{
    return visit('/admin/login')
        ->fill('input[type="email"]', test()->user->email)
        ->fill('input[type="password"]', 'password')
        ->press('button[type="submit"]')
        ->assertPathIs('/admin');
}

it('signs in through the Filament login form', function () {
    signIn()->assertSee('Dashboard');
});

it('renders the scanner showcase without JavaScript errors', function () {
    signIn()
        ->navigate('/admin/scanner-showcase')
        ->assertSee('Scanner Showcase')
        ->assertSee('QR codes only')
        ->assertSee('Focus handoff pair')
        ->assertNoJavaScriptErrors();
});

it('decodes an uploaded QR image into the basic scanner field', function () {
    signIn()
        ->navigate('/admin/scanner-showcase')
        // Scope the click to the unrestricted basic scanner: the title
        // attribute repeats on every section's button.
        ->click('section:has-text("Basic scanner") >> button[title="Scan QR with camera"]')
        // The modal's file input is scoped the same way: every section
        // renders an identical input, and restricted sections (e.g. retail
        // barcodes) intentionally reject QR uploads via their formats() filter.
        ->attach('section:has-text("Basic scanner") >> input[type="file"]', __DIR__.'/Fixtures/qr-code-sample.png')
        ->wait(5)
        ->assertValue('input[placeholder="Scan any code..."]', 'technomed.asia');
});
