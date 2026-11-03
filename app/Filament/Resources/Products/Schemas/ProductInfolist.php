<?php

namespace App\Filament\Resources\Products\Schemas;

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

                // Code 128 takes any stored value; an invalid one shows an
                // inline error instead of breaking the page.
                BarcodeEntry::make('barcode_image')
                    ->label('Product Barcode')
                    ->data(fn ($record): ?string => $record->barcode)
                    ->type(BarcodeType::Code128)
                    ->columnSpanFull(),
            ]);
    }
}
