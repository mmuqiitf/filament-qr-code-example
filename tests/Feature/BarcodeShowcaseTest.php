<?php

use App\Filament\Pages\BarcodeShowcase;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('serves the page at /admin/barcode-showcase under the barcodeShowcase route name', function () {
    expect(BarcodeShowcase::getUrl())->toEndWith('/admin/barcode-showcase')
        ->and(BarcodeShowcase::getRouteName())->toBe('filament.admin.pages.barcodeShowcase');

    $this->get(BarcodeShowcase::getUrl())->assertSuccessful()->assertSee('Barcode Showcase');
});

it('renders one sample barcode for every supported symbology', function () {
    $component = Livewire::test(BarcodeShowcase::class);

    foreach (['Code 128', 'EAN-13', 'EAN-8', 'UPC-A', 'Code 39', 'ITF-14'] as $label) {
        $component->assertSee($label);
    }

    $component->assertSeeHtml('data-qr-part="barcode.image"')
        ->assertSeeHtml('data:image/svg+xml;base64,')
        ->assertDontSeeHtml('data-qr-part="barcode.error"');
});

it('switches the samples to PNG', function () {
    Livewire::test(BarcodeShowcase::class)
        ->set('data.format', 'png')
        ->assertSeeHtml('data:image/png;base64,');
});

it('shows an inline error for an invalid try-it value instead of failing the page', function () {
    Livewire::test(BarcodeShowcase::class)
        ->set('data.type', 'ean13')
        ->set('data.value', '123')
        ->assertSeeHtml('data-qr-part="barcode.error"')
        ->assertSee('Invalid EAN-13 value');
});

it('renders a valid try-it value', function () {
    Livewire::test(BarcodeShowcase::class)
        ->set('data.type', 'code39')
        ->set('data.value', 'HELLO-39')
        ->assertDontSeeHtml('data-qr-part="barcode.error"');
});

it('shows product barcodes as EAN-13, falling back to Code 128 for other values', function () {
    Product::factory()->create(['sku' => 'PRD-1001', 'barcode' => '8901234567005', 'name' => 'Wireless Scanner']);
    Product::factory()->create(['sku' => 'PRD-1002', 'barcode' => 'INT-0042', 'name' => 'Internal Part']);

    Livewire::test(BarcodeShowcase::class)
        ->assertSee('Wireless Scanner')
        ->assertSeeHtml('alt="EAN-13 barcode: 8901234567005"')
        ->assertSeeHtml('alt="Code 128 barcode: INT-0042"');
});
