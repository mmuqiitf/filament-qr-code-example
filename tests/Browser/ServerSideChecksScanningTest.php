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

function recordSkuFieldClasses(mixed $page): void
{
    $page->script(<<<'JS'
        () => {
            const field = document.querySelector('[data-field-name="sku"]');
            window.__skuClasses = [];
            const start = performance.now();
            const record = () => window.__skuClasses.push([Math.round(performance.now() - start), field.className]);
            new MutationObserver(record).observe(document.body, { attributes: true, subtree: true, attributeFilter: ['class'] });
            window.__skuField = field;
        }
        JS);
}

function skuFlashSeen(mixed $page): array
{
    return $page->script(<<<'JS'
        () => ({
            classes: window.__skuClasses,
            sameNode: window.__skuField === document.querySelector('[data-field-name="sku"]'),
        })
        JS);
}

it('flashes the SKU field green after a valid upload closes the modal', function () {
    $page = signIn()->navigate('/admin/server-side-checks');
    recordSkuFieldClasses($page);

    $page->click('button[title="Scan QR with camera"]')
        ->attach('input[type="file"]', __DIR__.'/Fixtures/sku-valid-PRD-1001.png')
        ->wait(1);

    $seen = skuFlashSeen($page);
    // Regression guard: the ring must land on the field wrapper (not the hidden file input) within ~1 s.
    expect($seen['sameNode'])->toBeTrue()
        ->and(collect($seen['classes'])->pluck(1)->contains(fn ($c) => str_contains($c, 'fi-qr-flash-success')))->toBeTrue();
});

it('flashes the SKU field red after an out-of-stock upload', function () {
    $page = signIn()->navigate('/admin/server-side-checks');
    recordSkuFieldClasses($page);

    $page->click('button[title="Scan QR with camera"]')
        ->attach('input[type="file"]', __DIR__.'/Fixtures/sku-out-of-stock-PRD-1007.png')
        ->wait(1);

    expect(collect(skuFlashSeen($page)['classes'])->pluck(1)->contains(fn ($c) => str_contains($c, 'fi-qr-flash-danger')))->toBeTrue();
});
