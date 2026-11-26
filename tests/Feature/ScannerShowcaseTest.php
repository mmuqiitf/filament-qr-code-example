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
        ->assertSee('Focus handoff pair')
        ->assertSee('Warehouse preset and wide scan region');
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
            'guarded_sku' => 'GOOD-1',
            'warehouse_preset' => 'WH-900',
        ])
        ->assertHasNoFormErrors()
        ->assertSet('data.basic_sku', 'SKU-1')
        ->assertSet('data.retail_barcode', '8901234567890')
        ->assertSet('data.handoff_b', 'SECOND')
        ->assertSet('data.guarded_sku', 'GOOD-1')
        ->assertSet('data.warehouse_preset', 'WH-900');
});

it('normalizes retail barcodes live and rejects guarded scans instantly', function () {
    Livewire::test(ScannerShowcase::class)
        ->set('data.retail_barcode', '  8901234567890  ')
        ->assertSet('data.retail_barcode', '8901234567890')
        ->set('data.guarded_sku', '  good-42  ')
        ->assertSet('data.guarded_sku', 'GOOD-42')
        ->set('data.guarded_sku', 'BAD-001')
        ->assertDispatched('qr-scan-rejected')
        ->assertSet('data.guarded_sku', null);

    // The qr-scan-rejected listener surfaces the rejection as a notification.
    Livewire::test(ScannerShowcase::class)
        ->call('handleScanRejected', 'Reserved prefix — scan rejected.')
        ->assertNotified('Scan rejected');
});

it('syncs typed guarded SKUs on blur so rejectWhen also covers manual entry', function () {
    Livewire::test(ScannerShowcase::class)
        ->assertSeeHtml('wire:model.live.blur="data.guarded_sku"');
});

it('syncs typed retail barcodes on blur so normalizeUsing also covers manual entry', function () {
    Livewire::test(ScannerShowcase::class)
        ->assertSeeHtml('wire:model.live.blur="data.retail_barcode"');
});

it('exposes Warehouse preset formats and wide scanRegion on warehouse_preset', function () {
    $html = Livewire::test(ScannerShowcase::class)->html();

    expect($html)->toMatch('/data-field-name="warehouse_preset"[^>]*data-qr-options="[^"]+"/');

    preg_match('/data-field-name="warehouse_preset"[^>]*data-qr-options="([^"]+)"/', $html, $matches);

    $payload = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['fps'])->toBe(20)
        ->and($payload['formats'])->toBe(['CODE_128', 'CODE_39', 'ITF', 'QR_CODE', 'DATA_MATRIX'])
        ->and($payload['scanRegion'])->toBe(['width' => 0.88, 'height' => 0.35]);
});
