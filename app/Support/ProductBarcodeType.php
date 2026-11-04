<?php

namespace App\Support;

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

/**
 * Retail product barcodes are EAN-13; anything else (an internal code, a
 * typo'd check digit) still prints as Code 128, which takes any ASCII value.
 */
class ProductBarcodeType
{
    public static function for(?string $barcode): BarcodeType
    {
        if (blank($barcode)) {
            return BarcodeType::Ean13;
        }

        try {
            BarcodeService::validate(BarcodeType::Ean13, $barcode);

            return BarcodeType::Ean13;
        } catch (InvalidBarcodeException) {
            return BarcodeType::Code128;
        }
    }
}
