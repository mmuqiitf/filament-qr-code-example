<?php

use App\Filament\Forms\Components\CustomFormattedScanner;

it('configures custom formatted scanner and strips prefix', function () {
    $scanner = CustomFormattedScanner::make('barcode')
        ->prefixFilter('ITEM-');

    expect($scanner->getPrefix())->toBe('ITEM-');

    $formatted = $scanner->formatScannedValue('ITEM-12345');
    expect($formatted)->toBe('12345');

    $lowercaseWithPrefix = $scanner->formatScannedValue('item-abcde');
    expect($lowercaseWithPrefix)->toBe('ABCDE');
});
