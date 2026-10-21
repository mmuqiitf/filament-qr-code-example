<?php

use App\Filament\Pages\QrLabels;
use App\Models\AssemblyOrder;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner']);

    AssemblyOrder::factory()->create(['number' => 'WO-5001', 'product_name' => 'Scanner kit'])
        ->bomLines()->createMany([
            ['component_sku' => 'CMP-100', 'quantity' => 2],
            ['component_sku' => 'CMP-200', 'quantity' => 1],
        ]);
    AssemblyOrder::factory()->create(['number' => 'WO-5003', 'status' => 'done']);
});

it('serves the page at /admin/qr-labels under the qrLabels route name', function () {
    expect(QrLabels::getUrl())->toEndWith('/admin/qr-labels')
        ->and(QrLabels::getRouteName())->toBe('filament.admin.pages.qrLabels');

    $this->get(QrLabels::getUrl())->assertSuccessful()->assertSee('QR Labels');
});

it('lists the codes each scanning demo expects', function (string $set, array $codes) {
    $labels = Livewire::test(QrLabels::class)->set('data.set', $set)->instance()->labels();

    expect(array_column($labels, 'code'))->toBe($codes);
})->with([
    'products' => ['products', ['PRD-1001']],
    'work orders (open only)' => ['work-orders', ['WO-5001']],
    'components, one per BOM unit' => ['components', ['CMP-100:0001', 'CMP-100:0002', 'CMP-200:0001']],
    'badges (active only)' => ['badges', ['OP-001', 'OP-002']],
    'station codes' => ['station', ['DONE', 'PO-7001', 'LOT-A1', 'RW-07']],
]);

it('renders every label as an inline QR image on a printable label sheet', function () {
    Livewire::test(QrLabels::class)
        ->set('data.set', 'components')
        ->assertSeeHtml('filament-qr-label-sheet')
        ->assertSeeHtml('data:image/svg+xml;base64,')
        ->assertSee('CMP-100:0002');
});

it('downloads one label as SVG or PNG through the generator API', function () {
    Livewire::test(QrLabels::class)
        ->set('data.set', 'products')
        ->call('download', 'PRD-1001')
        ->assertFileDownloaded('qr-PRD-1001.svg');

    Livewire::test(QrLabels::class)
        ->set('data.set', 'products')
        ->set('data.format', 'png')
        ->call('download', 'PRD-1001')
        ->assertFileDownloaded('qr-PRD-1001.png');
});

it('only downloads codes from the current label set', function () {
    Livewire::test(QrLabels::class)
        ->set('data.set', 'products')
        ->call('download', 'WO-5001')
        ->assertNoFileDownloaded();
});

it('rejects an invalid colour instead of rendering black', function () {
    Livewire::test(QrLabels::class)
        ->fillForm(['color' => 'not-a-colour'])
        ->call('applyStyle')
        ->assertHasFormErrors(['color']);
});
