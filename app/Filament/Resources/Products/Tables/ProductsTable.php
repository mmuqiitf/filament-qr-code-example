<?php

namespace App\Filament\Resources\Products\Tables;

use App\Support\ProductBarcodeType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;
use Mmuqiitf\FilamentQrCode\Tables\Columns\BarcodeColumn;
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
                    // filamentQrCode.image route (persistent L2 cache behind it).
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

                BarcodeColumn::make('barcode')
                    ->label('Barcode')
                    ->data(fn ($record): ?string => $record->barcode)
                    // Retail EAN-13, or Code 128 for internal codes and bad check digits.
                    ->type(fn ($record): BarcodeType => ProductBarcodeType::for($record->barcode))
                    ->barWidth(1)
                    ->height(28)
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
                ActionGroup::make([
                    DownloadQrAction::make()
                        ->qrData(fn ($record): string => (string) $record->sku)
                        ->qrFileName(fn ($record): string => "product-{$record->sku}"),
                    ViewAction::make(),
                    EditAction::make(),
                ]),
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
