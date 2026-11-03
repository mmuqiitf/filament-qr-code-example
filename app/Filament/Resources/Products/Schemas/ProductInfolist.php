<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Support\ProductBarcodeType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Infolists\Components\BarcodeEntry;
use Mmuqiitf\FilamentQrCode\Infolists\Components\QrEntry;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Product Name')
                    ->weight('bold'),

                TextEntry::make('sku')
                    ->label('SKU')
                    ->copyable(),

                TextEntry::make('barcode')
                    ->label('Barcode')
                    ->badge(),

                TextEntry::make('price')
                    ->money('USD'),

                TextEntry::make('stock'),

                TextEntry::make('description')
                    ->columnSpanFull(),

                QrEntry::make('sku')
                    ->label('Official Product QR Code')
                    ->size(200)
                    ->caption('Scan to verify authenticity')
                    ->downloadable(true)
                    ->columnSpanFull(),

                // EAN-13 for retail codes, Code 128 for anything else.
                BarcodeEntry::make('barcode_image')
                    ->label('Product Barcode')
                    ->data(fn ($record): ?string => $record->barcode)
                    ->type(fn ($record): BarcodeType => ProductBarcodeType::for($record->barcode))
                    ->columnSpanFull(),
            ]);
    }
}
