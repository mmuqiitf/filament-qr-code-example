<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render list products page', function () {
    Product::factory()->count(3)->create();

    Livewire::test(ListProducts::class)
        ->assertSuccessful();
});

it('can create a product using form schema with QrScanner', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Barcode Scanner Gun',
            'sku' => 'PRD-9999',
            'barcode' => '8901234569999',
            'price' => 199.99,
            'stock' => 25,
            'description' => 'Industrial wireless barcode scanner with cradle.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('sku', 'PRD-9999')->exists())->toBeTrue();
});

it('can render view product page with QrEntry', function () {
    $product = Product::factory()->create([
        'sku' => 'PRD-8888',
    ]);

    Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
        ->assertSuccessful();
});
