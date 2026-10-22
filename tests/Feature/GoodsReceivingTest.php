<?php

use App\Filament\Pages\GoodsReceiving;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner']);
});

function receivingSequence(mixed $test): QrScanSequence
{
    return $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);
}

function receivingScan(mixed $test, string $step, string $code): array
{
    $test->call('callSchemaComponentMethod', receivingSequence($test)->getKey(), 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => 'hardware']);

    return $test->effects['returns'][0];
}

it('serves the page at /admin/goods-receiving under the goodsReceiving route name', function () {
    expect(GoodsReceiving::getUrl())->toEndWith('/admin/goods-receiving')
        ->and(GoodsReceiving::getRouteName())->toBe('filament.admin.pages.goodsReceiving');

    $this->get(GoodsReceiving::getUrl())->assertSuccessful()->assertSee('Goods Receiving');
});

it('receives an item, skips the optional lot and keeps the PO for the next item', function () {
    $test = Livewire::test(GoodsReceiving::class);

    expect(receivingScan($test, 'po', 'PO-7001'))->toMatchArray(['passes' => true, 'next_step' => 'sku'])
        ->and(receivingScan($test, 'sku', 'PRD-1001'))->toMatchArray(['passes' => true, 'next_step' => 'lot'])
        // No lot label on this item: the first serial fits the next (repeat) step, so the lot is skipped.
        ->and(receivingScan($test, 'lot', 'SN-PRD-1001-0001'))->toMatchArray(['passes' => true, 'step' => 'serials', 'skipped' => ['lot'], 'next_step' => 'serials'])
        ->and(receivingScan($test, 'serials', 'SN-PRD-1001-0002'))->toMatchArray([
            'completed' => true,
            'submitted' => true,
            'reset' => true,
            'values' => ['po' => 'PO-7001'],
            'next_step' => 'sku',
        ]);

    $receipt = GoodsReceipt::sole();

    expect($receipt->po_number)->toBe('PO-7001')
        ->and($receipt->sku)->toBe('PRD-1001')
        ->and($receipt->lot_number)->toBeNull()
        ->and($receipt->serials)->toBe(['SN-PRD-1001-0001', 'SN-PRD-1001-0002']);
});

it('records the lot when one is scanned', function () {
    $test = Livewire::test(GoodsReceiving::class);

    receivingScan($test, 'po', 'PO-7001');
    receivingScan($test, 'sku', 'PRD-1001');
    expect(receivingScan($test, 'lot', 'LOT-A1'))->toMatchArray(['passes' => true, 'next_step' => 'serials']);
    receivingScan($test, 'serials', 'SN-PRD-1001-0001');
    receivingScan($test, 'serials', 'SN-PRD-1001-0002');

    expect(GoodsReceipt::sole()->lot_number)->toBe('LOT-A1');
});

it('rejects an unknown SKU and a duplicate serial', function () {
    $test = Livewire::test(GoodsReceiving::class);

    receivingScan($test, 'po', 'PO-7001');
    expect(receivingScan($test, 'sku', 'PRD-9999'))->toMatchArray(['passes' => false, 'next_step' => 'sku']);

    receivingScan($test, 'sku', 'PRD-1001');
    receivingScan($test, 'lot', 'LOT-A1');
    receivingScan($test, 'serials', 'SN-PRD-1001-0001');

    expect(receivingScan($test, 'serials', 'SN-PRD-1001-0001'))->toMatchArray(['passes' => false, 'next_step' => 'serials']);
});

it('jumps back to the serials when they belong to another SKU', function () {
    $test = Livewire::test(GoodsReceiving::class);

    receivingScan($test, 'po', 'PO-7001');
    receivingScan($test, 'sku', 'PRD-1001');
    receivingScan($test, 'lot', 'LOT-A1');
    receivingScan($test, 'serials', 'SN-PRD-2002-0001');

    expect(receivingScan($test, 'serials', 'SN-PRD-2002-0002'))->toMatchArray([
        'passes' => false,
        'invalid_step' => 'serials',
        'next_step' => 'serials',
        'message' => 'Serials must belong to PRD-1001.',
    ]);
    expect(GoodsReceipt::count())->toBe(0);
});

it('waits for the Receive button when auto-submit is off and re-checks on submit', function () {
    $test = Livewire::test(GoodsReceiving::class)->set('autoSubmit', false);

    receivingScan($test, 'po', 'PO-7001');
    receivingScan($test, 'sku', 'PRD-1001');
    receivingScan($test, 'lot', 'LOT-A1');
    receivingScan($test, 'serials', 'SN-PRD-1001-0001');

    expect(receivingScan($test, 'serials', 'SN-PRD-1001-0002'))->toMatchArray(['completed' => true, 'submitted' => null]);
    expect(GoodsReceipt::count())->toBe(0);

    $test->set('data.receipt.sku', 'PRD-9999')->call('submit')->assertHasErrors(['data.receipt.sku']);
    expect(GoodsReceipt::count())->toBe(0);

    $test->set('data.receipt.sku', 'PRD-1001')->call('submit')->assertHasNoErrors();
    expect(GoodsReceipt::count())->toBe(1);
    $test->assertSet('data.receipt', ['po' => 'PO-7001']);
});

it('collects as many serials as the item quantity', function () {
    $test = Livewire::test(GoodsReceiving::class)->set('quantity', 3);

    receivingScan($test, 'po', 'PO-7001');
    receivingScan($test, 'sku', 'PRD-1001');
    receivingScan($test, 'lot', 'LOT-A1');
    receivingScan($test, 'serials', 'SN-PRD-1001-0001');

    expect(receivingScan($test, 'serials', 'SN-PRD-1001-0002'))->toMatchArray(['completed' => false, 'next_step' => 'serials'])
        ->and(receivingScan($test, 'serials', 'SN-PRD-1001-0003'))->toMatchArray(['completed' => true]);
});

it('makes goods receipts from the factory', function () {
    $receipt = GoodsReceipt::factory()->create();

    expect($receipt->sku)->toBe($receipt->product->sku);
});
