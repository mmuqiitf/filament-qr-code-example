<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
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
            ]);
    }
}
