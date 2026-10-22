<?php

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner']);
});

// signIn() lives in QrUploadScanningTest.php and reads test()->user.

// A keyboard-wedge scanner: the keys of one code plus Enter, fired back to back.
function wedgeBurst(mixed $page, string $code): void
{
    $keys = json_encode([...mb_str_split($code), 'Enter']);

    $page->script(<<<JS
        () => {
            for (const key of {$keys}) {
                (document.activeElement ?? document.body).dispatchEvent(
                    new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }),
                );
            }
        }
    JS);
    $page->wait(1);
}

it('renders the goods receiving page without JavaScript errors', function () {
    signIn()
        ->navigate('/admin/goods-receiving')
        ->assertSee('Goods Receiving')
        ->assertSee('Purchase order')
        ->assertNoJavaScriptErrors();
});

it('receives an item from handheld bursts without focusing anything', function () {
    $page = signIn()->navigate('/admin/goods-receiving')->waitForEvent('networkidle');

    foreach (['PO-7001', 'PRD-1001', 'SN-PRD-1001-0001', 'SN-PRD-1001-0002'] as $code) {
        wedgeBurst($page, $code);
    }

    $page->wait(1)->assertSee('Received Wireless Scanner')->assertSee('PO-7001')->assertNoJavaScriptErrors();

    expect(GoodsReceipt::sole())
        ->po_number->toBe('PO-7001')
        ->serials->toBe(['SN-PRD-1001-0001', 'SN-PRD-1001-0002']);
});

it('records a lot scanned between the SKU and the serials', function () {
    $page = signIn()->navigate('/admin/goods-receiving')->waitForEvent('networkidle');

    foreach (['PO-7001', 'PRD-1001', 'LOT-A1', 'SN-PRD-1001-0001', 'SN-PRD-1001-0002'] as $code) {
        wedgeBurst($page, $code);
    }

    $page->wait(1)->assertSee('Received Wireless Scanner')->assertNoJavaScriptErrors();

    expect(GoodsReceipt::sole()->lot_number)->toBe('LOT-A1');
});
