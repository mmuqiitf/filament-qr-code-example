<?php

use App\Filament\Pages\CycleCount;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Product::factory()->create(['sku' => 'PRD-1001', 'name' => 'Wireless Mouse']);
    Product::factory()->create(['sku' => 'PRD-1002', 'name' => 'Gaming Keyboard']);
    Product::factory()->create(['sku' => 'PRD-1003', 'name' => 'USB-C Hub']);
});

it('serves the cycle count page at /admin/cycle-count', function () {
    expect(CycleCount::getUrl())->toEndWith('/admin/cycle-count')
        ->and(CycleCount::getRouteName())->toBe('filament.admin.pages.cycleCount');

    $this->get(CycleCount::getUrl())
        ->assertSuccessful()
        ->assertSee('Cycle Count');
});

it('records quantity items and total units on submit', function () {
    Livewire::test(CycleCount::class)
        ->fillForm([
            'location' => 'Aisle B',
            'items' => [
                ['code' => 'PRD-1001', 'qty' => 3],
                ['code' => 'PRD-1002', 'qty' => 2],
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified('Aisle B cycle count saved')
        ->assertFormSet(['items' => []])
        ->assertDispatched('cycle-count-recorded')
        ->assertSee('2 SKU(s)')
        ->assertSee('5 unit(s)')
        ->assertSee('PRD-1001 ×3');
});

it('enforces maxItems, quantity validation, and known SKUs on submit', function (array $items) {
    foreach (range(4, 6) as $n) {
        Product::factory()->create(['sku' => "PRD-100{$n}"]);
    }

    Livewire::test(CycleCount::class)
        ->fillForm([
            'location' => 'Aisle A',
            'items' => $items,
        ])
        ->call('submit')
        ->assertHasFormErrors(['items']);
})->with([
    'invalid qty' => [[['code' => 'PRD-1001', 'qty' => 0]]],
    'unknown sku' => [[['code' => 'PRD-9999', 'qty' => 1]]],
    'exceeds maxItems 5' => [[
        ['code' => 'PRD-1001', 'qty' => 1],
        ['code' => 'PRD-1002', 'qty' => 1],
        ['code' => 'PRD-1003', 'qty' => 1],
        ['code' => 'PRD-1004', 'qty' => 1],
        ['code' => 'PRD-1005', 'qty' => 1],
        ['code' => 'PRD-1006', 'qty' => 1],
    ]],
]);
