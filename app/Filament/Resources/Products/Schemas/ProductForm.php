<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Product Name')
                    ->required()
                    ->maxLength(255),

                QrScanner::make('sku')
                    ->label('Product SKU (QR / Barcode Scanner)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->formats([
                        BarcodeFormat::QrCode,
                        BarcodeFormat::Ean13,
                        BarcodeFormat::Code128,
                        BarcodeFormat::Code39,
                    ])
                    ->scanFormat(fn (?string $rawValue): ?string => $rawValue ? strtoupper(trim($rawValue)) : null)
                    ->sound(true)
                    ->vibrate(true)
                    ->beepFrequency(660)
                    ->beepDuration(120)
                    ->vibrateDuration(200)
                    ->hardwareScanner(enabled: true, burstThresholdMs: 50, terminators: ['Enter', 'Tab'], minBarcodeLength: 2)
                    ->allowUpload(true)
                    ->placeholder('Scan QR/barcode with camera, wedge, or enter SKU...'),

                TextInput::make('barcode')
                    ->label('Alternative Barcode / EAN')
                    ->maxLength(50),

                TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00)
                    ->required(),

                TextInput::make('stock')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                QrCodeDisplay::make('product_qr')
                    ->label('Generated Product QR Code')
                    ->data(fn ($record, $get) => $record->sku ?? $get('sku'))
                    ->size(180)
                    ->color('#0f172a')
                    ->caption('Official Product SKU Verification QR')
                    ->downloadable(true)
                    ->columnSpanFull(),
            ]);
    }
}
