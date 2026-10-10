<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                QrColumn::make('sku_qr')
                    ->label('QR')
                    ->data(fn ($record) => $record->sku)
                    ->thumbnailSize(44)
                    ->modalSize(260)
                    ->previewable(true)
                    ->downloadable(true)
                    // Large previews load lazily through the signed
                    // filament-qr-code.image route (persistent L2 cache behind it).
                    ->lazyModal(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DownloadQrAction::make()
                    ->qrData(fn ($record): string => (string) $record->sku)
                    ->qrFileName(fn ($record): string => "product-{$record->sku}"),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DownloadQrBulkAction::make()
                        ->qrData('sku')
                        ->qrFileName(fn ($record): string => "product-{$record->sku}")
                        ->qrFormat(QrFormat::Png)
                        ->zipName('shelf-labels.zip'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
