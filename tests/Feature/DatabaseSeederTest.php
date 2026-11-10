<?php

use App\Models\Product;
use App\Models\ReceivedLine;
use App\Support\Gs1SampleLabels;
use Database\Seeders\DatabaseSeeder;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

it('seeds product barcodes that are valid EAN-13 codes', function () {
    $this->seed(DatabaseSeeder::class);

    $barcodes = Product::query()->pluck('barcode')->filter()->values();

    expect($barcodes)->not->toBeEmpty();

    $invalid = $barcodes->reject(function (string $barcode): bool {
        try {
            BarcodeService::validate(BarcodeType::Ean13, $barcode);

            return true;
        } catch (InvalidBarcodeException) {
            return false;
        }
    })->values()->all();

    expect($invalid)->toBe([]);
});

it('seeds products for the valid GS1 sample labels', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (Gs1SampleLabels::all() as $sample) {
        if ($sample['expected'] === null) {
            expect(Product::forGtin($sample['gtin'])?->sku)->not->toBeNull();
        }
    }
});

it('seeds received lines from valid GS1 labels linked to their product', function () {
    $this->travelTo('2026-11-10 09:00:00');

    $this->seed(DatabaseSeeder::class);

    $lines = ReceivedLine::query()->with('product')->get();

    expect($lines)->not->toBeEmpty();

    foreach ($lines as $line) {
        $label = Gs1::parse($line->label);

        expect($label->get('01'))->toBe($line->gtin)
            ->and($line->product->is(Product::forGtin($line->gtin)))->toBeTrue()
            ->and($label->date('17')?->toDateString())->toBe($line->expires_at->toDateString())
            ->and($line->expires_at->isFuture())->toBeTrue();
    }
});
