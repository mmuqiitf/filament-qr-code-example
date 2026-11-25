<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsAction;
use Mmuqiitf\FilamentQrCode\Tables\Columns\BarcodeColumn;

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

it('syncs typed SKUs on blur so the QR preview and reject hook refresh', function () {
    Livewire::test(CreateProduct::class)
        ->assertSeeHtml('wire:model.live.blur="data.sku"');
});

it('previews the SKU being edited, not the saved one', function () {
    $product = Product::factory()->create(['sku' => 'PRD-SAVED']);

    $savedPreview = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->html();

    $editedPreview = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['sku' => 'PRD-EDITED'])
        ->html();

    $qrImage = fn (string $html): string => preg_match('#<img src="(data:image/[^"]+)" alt="QR Code"#', $html, $m) ? $m[1] : '';

    expect($qrImage($editedPreview))->not->toBe('')
        ->and($qrImage($editedPreview))->not->toBe($qrImage($savedPreview));
});

it('keeps the products table narrow enough for a 1280px screen', function () {
    $table = Livewire::test(ListProducts::class)->instance()->getTable();

    $actions = $table->getRecordActions();
    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(ActionGroup::class)
        ->and(collect($actions[0]->getActions())->map(fn ($a) => $a->getName())->values()->all())
        ->toBe(class_exists(PrintLabelsAction::class)
            ? ['download_qr', 'print_labels', 'view', 'edit']
            : ['download_qr', 'view', 'edit']);

    expect($table->getColumn('barcode'))->toBeInstanceOf(BarcodeColumn::class);
});

it('shows product barcodes on the list as EAN-13, falling back to Code 128', function () {
    Product::factory()->create(['sku' => 'PRD-7781', 'barcode' => '8901234567012']);
    Product::factory()->create(['sku' => 'PRD-7782', 'barcode' => 'INT-0042']);

    Livewire::test(ListProducts::class)
        ->assertSeeHtml('alt="EAN-13 barcode: 8901234567012"')
        ->assertSeeHtml('alt="Code 128 barcode: INT-0042"')
        ->searchTable('8901234567012')
        ->assertSeeHtml('alt="EAN-13 barcode: 8901234567012"')
        ->assertDontSeeHtml('alt="Code 128 barcode: INT-0042"');
});

it('shows a valid product barcode as EAN-13 on the view page', function () {
    $product = Product::factory()->create(['sku' => 'PRD-7777', 'barcode' => '8901234567012']);

    Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
        ->assertSeeHtml('alt="EAN-13 barcode: 8901234567012"')
        ->assertSeeHtml('download="barcode.svg"');
});

it('falls back to Code 128 for a product barcode that is not a valid EAN-13', function () {
    $product = Product::factory()->create(['sku' => 'PRD-7778', 'barcode' => '8901234567891']);

    Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
        ->assertSeeHtml('alt="Code 128 barcode: 8901234567891"');
});

it('builds a Digital Link QR and GS1 DataMatrix for a product print label', function () {
    if (! class_exists(PrintLabelsAction::class)) {
        $this->markTestSkipped('needs filament-qr-code 1.3 print actions');
    }

    $product = Product::factory()->create([
        'sku' => 'PRD-PRINT',
        'name' => 'Printable Widget',
        'barcode' => '8901234567012',
    ]);

    $label = PrintLabelsAction::make()
        ->labelUsing(fn ($record) => ProductsTable::productLabel($record))
        ->labelsForRecords(collect([$product]))[0];

    $gtin = $product->gtin14();

    expect($gtin)->toBe('08901234567012')
        ->and($label->qrPayload())->toBe(DigitalLink::build(['01' => $gtin, '10' => 'PRD-PRINT']))
        ->and($label->getHeading())->toBe('PRD-PRINT')
        ->and($label->getCaption())->toBe('Printable Widget')
        ->and($label->barcodeDataUri())->toStartWith('data:image/');
});

it('opens a printable Avery sheet for a product with a valid GTIN', function () {
    if (! class_exists(PrintLabelsAction::class)) {
        $this->markTestSkipped('needs filament-qr-code 1.3 print actions');
    }

    $product = Product::factory()->create([
        'sku' => 'PRD-SHEET',
        'name' => 'Sheet Widget',
        'barcode' => '8901234567012',
    ]);

    $url = PrintLabelsAction::make()
        ->sheet(LabelSheet::avery5160())
        ->labelUsing(fn ($record) => ProductsTable::productLabel($record))
        ->storePrint(collect([$product]));

    expect($url)->not->toBeNull();

    $this->get($url)
        ->assertOk()
        ->assertSee('PRD-SHEET', escape: false)
        ->assertSee('Sheet Widget', escape: false)
        ->assertSee('filament-qr-label-sheet', escape: false)
        ->assertSee('data:image/', escape: false);
});

it('pads a valid product EAN-13 to GTIN-14 and ignores invalid or non-numeric barcodes', function () {
    expect(Product::factory()->make(['barcode' => '8901234567012'])->gtin14())->toBe('08901234567012')
        ->and(Product::factory()->make(['barcode' => '8901234567891'])->gtin14())->toBeNull()
        ->and(Product::factory()->make(['barcode' => 'INT-0042'])->gtin14())->toBeNull()
        ->and(Product::factory()->make(['barcode' => null])->gtin14())->toBeNull();
});

it('falls back to a SKU QR when the product barcode is not a valid GTIN', function () {
    if (! class_exists(PrintLabelsAction::class)) {
        $this->markTestSkipped('needs filament-qr-code 1.3 print actions');
    }

    $product = Product::factory()->create([
        'sku' => 'PRD-FALLBACK',
        'name' => 'Internal Part',
        'barcode' => 'INT-0042',
    ]);

    $label = PrintLabelsAction::make()
        ->labelUsing(fn ($record) => ProductsTable::productLabel($record))
        ->labelsForRecords(collect([$product]))[0];

    expect($label->qrPayload())->toBe('PRD-FALLBACK')
        ->and($label->getHeading())->toBe('PRD-FALLBACK')
        ->and($label->getCaption())->toBe('Internal Part')
        ->and($label->barcodeDataUri())->toStartWith('data:image/');
});
