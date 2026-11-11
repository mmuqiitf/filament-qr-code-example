<?php

use App\Models\Product;
use App\Models\ReceivedLine;
use App\Models\User;

// axe-core (bundled with pestphp/pest-plugin-browser) against the GS1 pages.
// signIn() lives in QrUploadScanningTest.php; wedgeBurst() in GoodsReceivingScanningTest.php.

beforeEach(function () {
    $this->user = User::factory()->create();

    $product = Product::factory()->create(['sku' => 'PRD-1008', 'barcode' => '8901234567074', 'name' => 'Thermal Label Rolls']);

    ReceivedLine::factory()->for($product)->create([
        'label' => '(01)08901234567074(17)271110(10)LOT-2611A',
        'gtin' => '08901234567074',
        'batch' => 'LOT-2611A',
    ]);
});

it('has no accessibility issues on the GS1 Labels page', function () {
    signIn()
        ->navigate('/admin/gs1-labels')
        ->waitForEvent('networkidle')
        ->assertNoAccessibilityIssues(level: 3);
});

it('has no accessibility issues on the GS1 Receiving page', function () {
    signIn()
        ->navigate('/admin/gs1-receiving')
        ->waitForEvent('networkidle')
        ->assertNoAccessibilityIssues(level: 3);
});

it('has no accessibility issues on the GS1 Receiving page with a rejection under the field', function () {
    $page = signIn()->navigate('/admin/gs1-receiving')->waitForEvent('networkidle');

    wedgeBurst($page, '8901234567074');

    $page->assertSee('Plain product barcode, not a GS1 label')
        ->assertNoAccessibilityIssues(level: 3);
});
