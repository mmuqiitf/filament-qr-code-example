<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render list products page', function () {
    Product::factory()->count(3)->create();

    Livewire::test(ListProducts::class)
        ->assertSuccessful();
});

it('can create a product using form schema with QrScanner', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Barcode Scanner Gun',
            'sku' => 'PRD-9999',
            'barcode' => '8901234569999',
            'price' => 199.99,
            'stock' => 25,
            'description' => 'Industrial wireless barcode scanner with cradle.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('sku', 'PRD-9999')->exists())->toBeTrue();
});

it('can render view product page with QrEntry', function () {
    $product = Product::factory()->create([
        'sku' => 'PRD-8888',
    ]);

    Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
        ->assertSuccessful();
});

it('normalizes SKUs live and rejects reserved prefixes instantly', function () {
    Livewire::test(CreateProduct::class)
        ->set('data.sku', '  prd-7700  ')
        ->assertSet('data.sku', 'PRD-7700')
        ->set('data.sku', 'BAD-001')
        ->assertDispatched('qr-scan-rejected')
        ->assertSet('data.sku', null);
});

it('exports selected products as a QR ZIP via the bulk action', function () {
    $products = Product::factory()->count(2)->create();

    $files = DownloadQrBulkAction::make()
        ->qrData('sku')
        ->qrFileName(fn ($record): string => "product-{$record->sku}")
        ->qrFormat(QrFormat::Png)
        ->recordsToFiles($products);

    expect(array_keys($files))->toHaveCount(2);

    foreach ($files as $name => $bytes) {
        expect($name)->toEndWith('.png')
            ->and($bytes)->not->toBeEmpty();
    }
});
