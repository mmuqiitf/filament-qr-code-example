<?php

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1001', 'barcode' => '8901234567891', 'name' => 'Wireless Scanner']);
});

it('renders a barcode for every symbology and the product barcodes', function () {
    signIn()
        ->navigate('/admin/barcode-showcase')
        ->waitForEvent('networkidle')
        ->assertSee('Barcode Showcase')
        ->assertPresent('img[alt="EAN-13 barcode: 5901234123457"]')
        ->assertPresent('img[alt="ITF-14 barcode: 15400141288763"]')
        ->assertPresent('img[alt="Code 128 barcode: 8901234567891"]')
        ->assertPresent('a[download="barcode.svg"]')
        ->assertNoJavaScriptErrors();
});

it('shows an inline error for an invalid value typed into Try it', function () {
    $page = signIn()
        ->navigate('/admin/barcode-showcase')
        ->waitForEvent('networkidle');

    $page->script(<<<'JS'
        () => Livewire.find(document.querySelector('.fi-page[wire\\:id]').getAttribute('wire:id')).set('data.value', '123')
    JS);

    $page->wait(1)
        ->assertPresent('[role="alert"][data-qr-part="barcode.error"]')
        ->assertSee('Invalid EAN-13 value')
        ->assertNoJavaScriptErrors();
});
