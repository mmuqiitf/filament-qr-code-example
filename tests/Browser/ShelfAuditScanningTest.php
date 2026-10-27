<?php

use App\Models\Product;
use App\Models\StockCount;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Mouse']);
    Product::factory()->create(['sku' => 'PRD-1002', 'name' => 'Gaming Keyboard']);
});

// signIn() lives in QrUploadScanningTest.php, wedgeBurst() in GoodsReceivingScanningTest.php.

it('collects handheld bursts, ignores a repeat and records the count', function () {
    $page = signIn()->navigate('/admin/shelf-audit')->waitForEvent('networkidle');

    foreach (['PRD-1001', 'PRD-1001'] as $code) {
        wedgeBurst($page, $code);
    }

    $page->assertSee('Wireless Mouse')
        ->assertScript("document.querySelectorAll('.fi-qr-collector-items .font-mono').length", 1)
        ->click('Record count')
        ->wait(1)
        ->assertSee('Aisle A counted')
        // The collector's own list empties for the next shelf.
        ->assertScript("document.querySelectorAll('.fi-qr-collector-items .font-mono').length", 0)
        ->assertNoJavaScriptErrors();

    expect(StockCount::sole())
        ->counted->toBe(['PRD-1001'])
        ->missing->toBe(['PRD-1002']);
});

it('flags an unknown code and blocks submit until it is removed', function () {
    $page = signIn()->navigate('/admin/shelf-audit')->waitForEvent('networkidle');

    wedgeBurst($page, 'BAD-9');

    $page->assertSee('Unknown code BAD-9')
        ->click('Record count')
        ->wait(1)
        ->assertSee('Remove them before submitting');

    expect(StockCount::count())->toBe(0);

    $page->click('.fi-qr-collector-items button[aria-label]')->wait(1);
    wedgeBurst($page, 'PRD-1002');

    $page->click('Record count')->wait(1)->assertSee('Aisle A counted')->assertNoJavaScriptErrors();

    expect(StockCount::sole()->counted)->toBe(['PRD-1002']);
});
