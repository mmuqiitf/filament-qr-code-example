<?php

use App\Filament\Pages\CycleCount;
use App\Filament\Pages\DigitalLinkShowcase;
use App\Filament\Pages\GoodsReceiving;
use App\Filament\Pages\ManufacturingStation;
use App\Filament\Pages\PosCheckout;
use App\Filament\Pages\QrLabels;
use App\Filament\Pages\ScannerShowcase;
use App\Filament\Pages\ServerSideChecks;
use App\Filament\Pages\ShelfAudit;
use App\Filament\Pages\WorkOrderSequence;
use App\Models\User;
use Livewire\Livewire;

// Filament's callout ignores its default slot, so an intro placed there never
// renders. Each page keeps its intro in the description (and footer) slots.

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('shows the intro on the page', function (string $page, string $title, string $intro) {
    Livewire::test($page)
        ->assertSeeInOrder([$title, $intro], escape: false);
})->with([
    'goods receiving' => [GoodsReceiving::class, 'Receive with a handheld scanner', 'Scan PO-7001, then PRD-1001'],
    'POS' => [PosCheckout::class, 'Scan, scan, scan', 'Try PRD-1001 twice (quantity 2)'],
    'manufacturing' => [ManufacturingStation::class, 'Build a unit with a handheld scanner', 'Scan your badge (OP-001)'],
    'QR Labels' => [QrLabels::class, 'Print the codes the scanning demos expect', 'Pick a label set, print the sheet'],
    'scanner showcase' => [ScannerShowcase::class, 'One field, many configurations', 'Type or scan into any scenario below.'],
    'server-side checks' => [ServerSideChecks::class, 'Checked on the server', 'Try PRD-1001 (accepted), PRD-9999 (unknown SKU)'],
    'shelf audit' => [ShelfAudit::class, 'Count a shelf with the batch collector', 'Scan every product label on the shelf'],
    'work-order sequence' => [WorkOrderSequence::class, 'How Sequential Scanning Works', 'Untick corrections above to lock steps read-only.'],
    'digital link' => [DigitalLinkShowcase::class, 'GS1 Digital Link', 'Build, encode and parse a GS1 Digital Link.'],
    'cycle count' => [CycleCount::class, 'Cycle count with quantities, CSV, and item cap', 'Rescan PRD-1001, PRD-1002, or PRD-1003'],
]);

it('does not render the intro title as an out-of-order h4', function (string $page) {
    Livewire::test($page)->assertDontSeeHtml('fi-callout-heading');
})->with([
    GoodsReceiving::class, PosCheckout::class, ManufacturingStation::class, QrLabels::class,
    ScannerShowcase::class, ServerSideChecks::class, ShelfAudit::class, WorkOrderSequence::class,
    DigitalLinkShowcase::class, CycleCount::class,
]);
