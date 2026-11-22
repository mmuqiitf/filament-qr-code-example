<?php

use App\Models\Product;
use App\Models\ReceivedLine;
use App\Models\User;
use App\Support\Gs1SampleLabels;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

// Each label on the GS1 Labels page, pasted into the field and sent with
// Enter, the way a person copies it across. signIn() lives in
// QrUploadScanningTest.php.

beforeEach(function () {
    $this->user = User::factory()->create();

    Product::factory()->create(['sku' => 'PRD-1008', 'barcode' => substr(Gs1SampleLabels::LABEL_ROLLS_GTIN, 1), 'name' => 'Thermal Label Rolls']);
    Product::factory()->create(['sku' => 'PRD-1009', 'barcode' => substr(Gs1SampleLabels::BATTERY_PACK_GTIN, 1), 'name' => 'Battery Pack']);
    Product::factory()->create(['sku' => 'PRD-1010', 'barcode' => substr(Gs1SampleLabels::WIPES_GTIN, 1), 'name' => 'Cleaning Wipes']);
});

function pasteLabel(mixed $page, string $label): mixed
{
    return $page->fill('input[id="form.label"]', $label)
        ->keys('input[id="form.label"]', 'Enter')
        ->wait(1.5);
}

it('receives each accepted sample label pasted and sent with Enter', function (string $key) {
    $sample = Gs1SampleLabels::find($key);
    $page = signIn()->navigate('/admin/gs1-receiving')->waitForEvent('networkidle');

    pasteLabel($page, $sample['value'])
        ->assertSee('Received')
        ->assertDontSee('field is required')
        ->assertNoJavaScriptErrors();

    $parsed = Gs1::parse($sample['value']);

    expect(ReceivedLine::sole())
        ->gtin->toBe($sample['gtin'])
        ->quantity->toBe((int) ($parsed->get('30') ?? 1));
})->with(fn () => array_column(Gs1SampleLabels::valid(), 'key'));

it('receives the accepted sample labels one after another on the same page', function () {
    $page = signIn()->navigate('/admin/gs1-receiving')->waitForEvent('networkidle');

    foreach (array_reverse(Gs1SampleLabels::valid()) as $sample) {
        pasteLabel($page, $sample['value'])->assertDontSee('field is required');
    }

    expect(ReceivedLine::orderBy('id')->pluck('quantity', 'gtin')->all())->toBe([
        Gs1SampleLabels::WIPES_GTIN => 1,
        Gs1SampleLabels::BATTERY_PACK_GTIN => 1,
        Gs1SampleLabels::LABEL_ROLLS_GTIN => 12,
    ]);
});

it('keeps the reason for a rejected sample label sent with Enter, and receives nothing', function (string $key) {
    $sample = Gs1SampleLabels::find($key);
    $page = signIn()->navigate('/admin/gs1-receiving')->waitForEvent('networkidle');

    pasteLabel($page, $sample['value'])
        ->assertSee($sample['expected'])
        ->assertDontSee('field is required')
        ->assertNoJavaScriptErrors();

    expect(ReceivedLine::count())->toBe(0);
})->with(fn () => array_column(array_filter(Gs1SampleLabels::all(), fn (array $s): bool => $s['expected'] !== null), 'key'));
