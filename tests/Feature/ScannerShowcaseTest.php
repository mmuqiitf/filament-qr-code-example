<?php

use App\Filament\Pages\ScannerShowcase;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render scanner showcase page', function () {
    Livewire::test(ScannerShowcase::class)
        ->assertSuccessful()
        ->assertSee('Scanner Showcase')
        ->assertSee('QR codes only')
        ->assertSee('Focus handoff pair');
});

it('binds every showcase scenario to form state', function () {
    Livewire::test(ScannerShowcase::class)
        ->fillForm([
            'basic_sku' => 'SKU-1',
            'qr_only' => 'https://example.com',
            'retail_barcode' => '8901234567890',
            'no_upload' => 'CAM-ONLY-1',
            'custom_feedback' => 'TUNED-1',
            'handoff_a' => 'FIRST',
            'handoff_b' => 'SECOND',
        ])
        ->assertHasNoFormErrors()
        ->assertSet('data.basic_sku', 'SKU-1')
        ->assertSet('data.retail_barcode', '8901234567890')
        ->assertSet('data.handoff_b', 'SECOND');
});
