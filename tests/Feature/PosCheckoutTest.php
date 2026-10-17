<?php

use App\Filament\Pages\PosCheckout;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\ContinuousDisplay;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner', 'price' => 129.50]);
    Product::factory()->create(['sku' => 'PRD-1002', 'name' => 'Label Printer', 'price' => 80]);
});

function posScanner(mixed $test): QrScanner
{
    return $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);
}

function posScan(mixed $test, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', posScanner($test)->getKey(), 'handleScan', ['code' => $code, 'source' => 'hardware']);
}

it('serves the page at /admin/pos-checkout under the posCheckout route name', function () {
    expect(PosCheckout::getUrl())->toEndWith('/admin/pos-checkout')
        ->and(PosCheckout::getRouteName())->toBe('filament.admin.pages.posCheckout');

    $this->get(PosCheckout::getUrl())->assertSuccessful()->assertSee('POS Checkout');
});

it('scans continuously on the whole page', function () {
    $scanner = posScanner(Livewire::test(PosCheckout::class));

    expect($scanner->getMode())->toBe(ScanMode::Continuous)
        ->and($scanner->getHardwareInputScope())->toBe(HardwareInputScope::Page);
});

it('merges the same SKU into one line with quantity 2', function () {
    $test = Livewire::test(PosCheckout::class);

    posScan($test, 'PRD-1001');
    posScan($test, 'PRD-1002');
    posScan($test, 'PRD-1001')->assertSet('data.scan', null);

    // Livewire hydrates 80.0 back as 80, so compare loosely.
    expect(array_values($test->get('cart')))->toEqual([
        ['sku' => 'PRD-1001', 'name' => 'Wireless Scanner', 'price' => 129.5, 'quantity' => 2],
        ['sku' => 'PRD-1002', 'name' => 'Label Printer', 'price' => 80.0, 'quantity' => 1],
    ]);
    $test->assertSee('339.00');
});

it('rejects an unknown SKU and leaves the cart alone', function () {
    $test = Livewire::test(PosCheckout::class);

    posScan($test, 'PRD-9999')->assertReturned(fn (array $result): bool => $result['passes'] === false);

    expect($test->get('cart'))->toBe([]);
});

it('applies the live option switches to the scanner', function () {
    $test = Livewire::test(PosCheckout::class)
        ->set('keepLastCode', true)
        ->set('pauseOnReject', true)
        ->set('keepCameraOpen', true);

    $scanner = posScanner($test);

    expect($scanner->getContinuousDisplay())->toBe(ContinuousDisplay::LastCode)
        ->and($scanner->shouldPauseOnReject())->toBeTrue()
        ->and($scanner->shouldKeepCameraOpen())->toBeTrue();

    posScan($test, 'PRD-1001')->assertSet('data.scan', 'PRD-1001');
});

it('uses the package defaults when the switches are off', function () {
    $scanner = posScanner(Livewire::test(PosCheckout::class));

    expect($scanner->getContinuousDisplay())->toBe(ContinuousDisplay::Empty)
        ->and($scanner->shouldPauseOnReject())->toBeFalse()
        ->and($scanner->shouldKeepCameraOpen())->toBeFalse();
});

it('clears the cart', function () {
    $test = Livewire::test(PosCheckout::class);
    posScan($test, 'PRD-1001');

    $test->callAction('clearCart');

    expect($test->get('cart'))->toBe([]);
});
