<?php

use App\Filament\Pages\BarcodeShowcase;
use App\Filament\Pages\CashierCheckout;
use App\Filament\Pages\GoodsReceiving;
use App\Filament\Pages\Gs1Labels;
use App\Filament\Pages\Gs1Receiving;
use App\Filament\Pages\ManufacturingStation;
use App\Filament\Pages\PosCheckout;
use App\Filament\Pages\QrCustomizer;
use App\Filament\Pages\QrLabels;
use App\Filament\Pages\ScannerShowcase;
use App\Filament\Pages\ServerSideChecks;
use App\Filament\Pages\ShelfAudit;
use App\Filament\Pages\WorkOrderSequence;
use App\Filament\Resources\Products\ProductResource;

it('gives every page in a group its own navigation sort', function () {
    expect([
        ScannerShowcase::getNavigationSort(),
        ServerSideChecks::getNavigationSort(),
        WorkOrderSequence::getNavigationSort(),
        PosCheckout::getNavigationSort(),
        CashierCheckout::getNavigationSort(),
        GoodsReceiving::getNavigationSort(),
        Gs1Receiving::getNavigationSort(),
        ManufacturingStation::getNavigationSort(),
        ShelfAudit::getNavigationSort(),
        QrCustomizer::getNavigationSort(),
    ])->toBe([0, 1, 2, 3, 4, 5, 6, 7, 8, 9])
        ->and([
            QrLabels::getNavigationSort(),
            BarcodeShowcase::getNavigationSort(),
            Gs1Labels::getNavigationSort(),
        ])->toBe([1, 2, 3])
        ->and(ProductResource::getNavigationSort())->toBe(1);
});
