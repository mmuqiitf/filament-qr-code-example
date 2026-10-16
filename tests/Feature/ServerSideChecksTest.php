<?php

use App\Filament\Pages\ServerSideChecks;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->inStock = Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner', 'price' => 129.50, 'stock' => 12]);
    $this->outOfStock = Product::factory()->create(['sku' => 'PRD-1007', 'name' => 'Rugged Terminal', 'price' => 899, 'stock' => 0]);
});

function skuScannerKey(mixed $test): string
{
    return $test->instance()->form
        ->getComponent(fn ($component): bool => $component instanceof QrScanner && $component->getName() === 'sku')
        ->getKey();
}

function scanSku(string $code, string $source = 'camera'): mixed
{
    $test = Livewire::test(ServerSideChecks::class);

    return $test->call('callSchemaComponentMethod', skuScannerKey($test), 'handleScan', ['code' => $code, 'source' => $source]);
}

it('renders the server-side checks page', function () {
    Livewire::test(ServerSideChecks::class)
        ->assertSuccessful()
        ->assertSee('Server-side checks');
});

it('accepts a known in-stock SKU and fills name and price', function () {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);

    scanSku('  PRD-1001 ', 'upload')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true
            && $result['code'] === 'PRD-1001'
            && $result['source'] === 'upload'
            && $result['record_key'] === $this->inStock->getKey())
        ->assertSet('data.sku', 'PRD-1001')
        ->assertSet('data.name', 'Wireless Scanner')
        ->assertSet('data.price', '129.50')
        ->assertNotNotified('Scan rejected');

    Event::assertDispatched(ScanAccepted::class);
    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event): bool => $event->code === 'PRD-1001' && $event->source === 'upload');
    Event::assertNotDispatched(ScanRejected::class);
});

it('rejects an unknown SKU, clears the field and notifies', function () {
    Event::fake([ScanAccepted::class, ScanRejected::class]);

    $test = Livewire::test(ServerSideChecks::class)
        ->set('data.sku', 'PRD-1001')
        ->set('data.name', 'Stale');

    $test->call('callSchemaComponentMethod', skuScannerKey($test), 'handleScan', ['code' => 'PRD-9999', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && str_contains((string) $result['message'], 'PRD-9999'))
        ->assertSet('data.sku', null)
        ->assertSet('data.name', null)
        ->assertNotified('Scan rejected');

    Event::assertDispatched(ScanRejected::class);
    Event::assertNotDispatched(ScanAccepted::class);
});

it('rejects a SKU that fails the format rule before the lookup', function () {
    scanSku('abc')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] !== null)
        ->assertSet('data.sku', null);
});

it('rejects an out-of-stock SKU through the closure check', function () {
    scanSku('PRD-1007', 'hardware')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'Rugged Terminal is out of stock.')
        ->assertSet('data.sku', null)
        ->assertSet('data.price', null)
        ->assertNotified('Scan rejected');
});

it('accepts a valid SKU on submit', function () {
    Livewire::test(ServerSideChecks::class)
        ->fillForm(['sku' => 'PRD-1001'])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified('Product PRD-1001 confirmed');
});

it('re-checks the SKU on submit when it went out of stock after the scan', function () {
    $test = scanSku('PRD-1001')->assertSet('data.sku', 'PRD-1001');

    $this->inStock->update(['stock' => 0]);

    $test->call('submit')
        ->assertHasFormErrors(['sku'])
        ->assertNotNotified('Product PRD-1001 confirmed');
});

it('re-checks typed unknown SKUs on submit without running scan callbacks', function () {
    Livewire::test(ServerSideChecks::class)
        ->fillForm(['sku' => 'PRD-9999'])
        ->call('submit')
        ->assertHasFormErrors(['sku'])
        ->assertNotNotified('Scan rejected');
});

it('exposes the cooldown and clear-on-reject settings to the browser', function () {
    $test = Livewire::test(ServerSideChecks::class);
    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanChecksScanPayload())
        ->toMatchArray(['serverCheck' => true, 'cooldownMs' => 2000, 'clearOnReject' => true]);
});
