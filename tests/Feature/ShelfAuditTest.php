<?php

use App\Filament\Pages\ShelfAudit;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\User;
use Filament\Notifications\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Mouse']);
    Product::factory()->create(['sku' => 'PRD-1002', 'name' => 'Gaming Keyboard']);
    Product::factory()->create(['sku' => 'PRD-1003', 'name' => 'USB-C Hub']);
});

it('serves the page at /admin/shelf-audit under the shelfAudit route name', function () {
    expect(ShelfAudit::getUrl())->toEndWith('/admin/shelf-audit')
        ->and(ShelfAudit::getRouteName())->toBe('filament.admin.pages.shelfAudit');

    $this->get(ShelfAudit::getUrl())->assertSuccessful()->assertSee('Shelf Audit');
});

it('looks each collected code up and names the product', function () {
    Livewire::test(ShelfAudit::class)
        ->call('handleCollectorScan', 'PRD-1001')
        ->assertNotified(Notification::make()->title('Wireless Mouse')->body('PRD-1001 counted.')->success());
});

it('flags a code that is not a product', function () {
    Livewire::test(ShelfAudit::class)
        ->call('handleCollectorScan', 'BAD-9')
        ->assertNotified(Notification::make()->title('Unknown code BAD-9')->body('Remove it (×) before submitting.')->danger());
});

it('records the count with the products not seen on the shelf', function () {
    Livewire::test(ShelfAudit::class)
        ->fillForm(['location' => 'Aisle A', 'codes' => ['PRD-1001', 'PRD-1003']])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified('Aisle A counted')
        ->assertFormSet(['codes' => []]);

    expect(StockCount::sole())
        ->location->toBe('Aisle A')
        ->counted->toBe(['PRD-1001', 'PRD-1003'])
        ->missing->toBe(['PRD-1002']);
});

it('rejects unknown and duplicate codes on submit', function (array $codes) {
    Livewire::test(ShelfAudit::class)
        ->fillForm(['location' => 'Aisle A', 'codes' => $codes])
        ->call('submit')
        ->assertHasFormErrors();

    expect(StockCount::count())->toBe(0);
})->with([
    'unknown' => [['PRD-1001', 'BAD-9']],
    'duplicate' => [['PRD-1001', 'PRD-1001']],
    'empty' => [[]],
]);
