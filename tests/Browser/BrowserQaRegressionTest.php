<?php

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\User;

// Regressions from browser QA of the demo pages. signIn() lives in
// QrUploadScanningTest.php; wedgeBurst() in GoodsReceivingScanningTest.php.

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Scanner', 'price' => 10]);
});

function sequenceStepValues(mixed $page): array
{
    return $page->script("() => [...document.querySelectorAll('input[data-qr-step]')].map((input) => input.value)");
}

function scanWorkOrderSteps(mixed $page): void
{
    foreach (['BATCH-42', 'OP-001', 'MC-09'] as $code) {
        wedgeBurst($page, $code);
    }

    expect(sequenceStepValues($page))->toBe(['BATCH-42', 'OP-001', 'MC-09']);
}

it('clears the work order sequence with Clear All, without a server error', function () {
    $page = signIn()->navigate('/admin/work-order-sequence')->waitForEvent('networkidle');
    scanWorkOrderSteps($page);

    $page->fill('textarea[id="form.notes"]', 'Inspect passed')
        ->press('Clear All')
        ->wait(1.5)
        ->assertDontSee('DirectlyCallingLifecycleHooksNotAllowed')
        ->assertValue('textarea[id="form.notes"]', '');

    expect(sequenceStepValues($page))->toBe(['', '', '']);
});

it('resets the scanned steps with the Clear Sequence header button', function () {
    $page = signIn()->navigate('/admin/work-order-sequence')->waitForEvent('networkidle');
    scanWorkOrderSteps($page);

    $page->press('Clear Sequence')->wait(1.5);

    expect(sequenceStepValues($page))->toBe(['', '', '']);
});

it('keeps fast typing in the notes out of the sequence steps', function () {
    $page = signIn()->navigate('/admin/work-order-sequence')->waitForEvent('networkidle');

    $page->typeSlowly('textarea[id="form.notes"]', 'mirror scratch on the left side', 15)->wait(1);

    $page->assertValue('textarea[id="form.notes"]', 'mirror scratch on the left side');

    expect(sequenceStepValues($page))->toBe(['', '', '']);
})->skip(fn (): bool => ! qrPackageHasQaFixes(), 'Needs filament-qr-code#92 (free text is not a burst).');

it('adds a line at the till when a code is typed and sent with Enter', function () {
    $page = signIn()->navigate('/admin/pos-checkout')->waitForEvent('networkidle');

    $page->click('input[placeholder="Scan PRD-1001..."]')
        ->typeSlowly('input[placeholder="Scan PRD-1001..."]', 'PRD-1001', 120)
        ->keys('input[placeholder="Scan PRD-1001..."]', 'Enter')
        ->wait(1.5)
        ->assertSee('Wireless Scanner')
        ->assertNoJavaScriptErrors();
})->skip(fn (): bool => ! qrPackageHasQaFixes(), 'Needs filament-qr-code#92 (Enter in continuous mode).');

it('does not report the camera as denied before Start is pressed', function (string $path) {
    signIn()->navigate($path)
        ->waitForEvent('networkidle')
        ->wait(1)
        ->assertDontSee('Camera access denied or unavailable.')
        ->assertNoJavaScriptErrors();
})->with([
    'goods receiving' => '/admin/goods-receiving',
    'manufacturing' => '/admin/manufacturing',
    'shelf audit' => '/admin/shelf-audit',
    'sequential scanning' => '/admin/work-order-sequence',
])->skip(fn (): bool => ! qrPackageHasQaFixes(), 'Needs filament-qr-code#92 (no camera request before Start).');

it('says why an item with too few serials is not received', function () {
    $page = signIn()->navigate('/admin/goods-receiving')->waitForEvent('networkidle');

    $page->fill('input[type="number"]', '3')->wait(1);

    foreach (['PO-7001', 'PRD-1001', 'SN-PRD-1001-0001', 'SN-PRD-1001-0002'] as $code) {
        wedgeBurst($page, $code);
    }

    $page->click('Receive item')
        ->wait(1.5)
        ->assertSee('Scan at least 3 code(s)')
        ->assertNoJavaScriptErrors();

    expect(GoodsReceipt::count())->toBe(0);
})->skip(fn (): bool => ! qrPackageHasQaFixes(), 'Needs filament-qr-code#92 (sequence step errors are shown).');
