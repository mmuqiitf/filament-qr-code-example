<?php

use App\Models\Product;
use App\Models\ReceivedLine;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1008', 'barcode' => '8901234567074', 'name' => 'Thermal Label Rolls']);
});

// signIn() lives in QrUploadScanningTest.php; wedgeBurst() in GoodsReceivingScanningTest.php.

it('renders the GS1 labels and the receiving page without JavaScript errors', function () {
    signIn()
        ->navigate('/admin/gs1-labels')
        ->waitForEvent('networkidle')
        ->assertSee('GS1 Labels')
        ->assertPresent('img[alt^="GS1-128 barcode: (01)08901234567074"]')
        ->assertPresent('[role="alert"][data-qr-part="barcode.error"]')
        ->assertNoJavaScriptErrors()
        ->navigate('/admin/gs1-receiving')
        ->waitForEvent('networkidle')
        ->assertSee('GS1 Receiving')
        ->assertNoJavaScriptErrors();
});

it('receives a line from a handheld burst with a group separator, without focusing the field', function () {
    $page = signIn()->navigate('/admin/gs1-receiving')->waitForEvent('networkidle');

    wedgeBurst($page, '0108901234567074'.'17'.now()->addYear()->format('ymd').'10L2611A'."\u{1D}".'3012');

    $page->assertValue('input[id="form.gtin"]', '08901234567074')
        ->assertValue('input[id="form.batch"]', 'L2611A')
        ->assertValue('input[id="form.quantity"]', '12')
        ->click('Receive line')
        ->wait(1)
        ->assertSee('Received 12 × Thermal Label Rolls')
        ->assertNoJavaScriptErrors();

    expect(ReceivedLine::sole()->batch)->toBe('L2611A');
});

it('shows why a plain EAN-13 is rejected under the field', function () {
    $page = signIn()->navigate('/admin/gs1-receiving')->waitForEvent('networkidle');

    wedgeBurst($page, '8901234567074');

    $page->assertSee('Plain product barcode, not a GS1 label')->assertNoJavaScriptErrors();

    expect(ReceivedLine::count())->toBe(0);
});
