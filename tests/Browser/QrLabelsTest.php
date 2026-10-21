<?php

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner']);
});

it('previews a printable label sheet and switches sets live', function () {
    $page = signIn()
        ->navigate('/admin/qr-labels')
        ->waitForEvent('networkidle')
        ->assertSee('QR Labels')
        ->assertSee('PRD-1001')
        ->assertPresent('.filament-qr-label-sheet img[alt="PRD-1001"]');

    // The page's own component (the topbar is a Livewire component too).
    $page->script(<<<'JS'
        () => Livewire.find(document.querySelector('.fi-page[wire\\:id]').getAttribute('wire:id')).set('data.set', 'badges')
    JS);

    $page->wait(1)->assertSee('OP-002')->assertNoJavaScriptErrors();
});
