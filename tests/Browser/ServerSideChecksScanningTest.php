<?php

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner', 'price' => 129.50, 'stock' => 12]);
    Product::factory()->create(['sku' => 'PRD-1007', 'name' => 'Rugged Terminal', 'price' => 899, 'stock' => 0]);
});

// signIn() lives in QrUploadScanningTest.php and reads test()->user.

function uploadSkuQr(string $fixture): mixed
{
    return signIn()
        ->navigate('/admin/server-side-checks')
        ->click('button[title="Scan QR with camera"]')
        ->attach('input[type="file"]', __DIR__.'/Fixtures/'.$fixture)
        ->wait(5);
}

it('renders the server-side checks page without JavaScript errors', function () {
    signIn()
        ->navigate('/admin/server-side-checks')
        ->assertSee('Server-side checks')
        ->assertNoJavaScriptErrors();
});

it('accepts an uploaded QR for an in-stock SKU and fills the product', function () {
    uploadSkuQr('sku-valid-PRD-1001.png')
        ->assertValue('input[placeholder="Scan PRD-1001..."]', 'PRD-1001')
        ->assertValue('#form\\.name, input[id$="data.name"]', 'Wireless Scanner');
});

it('rejects an uploaded QR for an out-of-stock SKU', function () {
    uploadSkuQr('sku-out-of-stock-PRD-1007.png')
        ->assertSee('Rugged Terminal is out of stock.')
        ->assertValue('input[placeholder="Scan PRD-1001..."]', '');
});

it('rejects an uploaded QR for an unknown SKU', function () {
    uploadSkuQr('sku-unknown-PRD-9999.png')
        ->assertSee('Scan rejected')
        ->assertValue('input[placeholder="Scan PRD-1001..."]', '');
});
