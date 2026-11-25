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
use Mmuqiitf\FilamentQrCode\Printing\Label;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsBulkAction;
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
                    ...self::printActions(),
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
                    ...self::printBulkActions(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return list<PrintLabelsAction>
     */
    protected static function printActions(): array
    {
        return [
            PrintLabelsAction::make()
                ->sheet(LabelSheet::avery5160())
                ->labelUsing(self::productLabel(...)),
        ];
    }

    /**
     * @return list<PrintLabelsBulkAction>
     */
    protected static function printBulkActions(): array
    {
        return [
            PrintLabelsBulkAction::make()
                ->sheet(LabelSheet::avery5160())
                ->labelUsing(self::productLabel(...)),
        ];
    }

    public static function productLabel(mixed $record): Label
    {
        $label = Label::make()
            ->heading((string) $record->sku)
            ->caption((string) $record->name);

        $gtin = $record->gtin14();

        if ($gtin !== null) {
            $label->digitalLink(['01' => $gtin, '10' => $record->sku])
                ->barcode('(01)'.$gtin.'(10)'.$record->sku, BarcodeType::Gs1DataMatrix);
        } else {
            $label->qr((string) $record->sku);

            if (filled($record->barcode)) {
                $label->barcode((string) $record->barcode, ProductBarcodeType::for($record->barcode));
            }
        }

        return $label;
    }
}
