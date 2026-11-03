<?php

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
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
