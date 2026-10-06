<?php

use App\Filament\Pages\CashierCheckout;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render cashier pos checkout page', function () {
    Livewire::test(CashierCheckout::class)
        ->assertSuccessful()
        ->assertSee('Cashier POS Checkout')
        ->assertSee('qr-hardware-scanned')
        ->assertDontSee('Batch QR Collector');
});

it('can scan a product barcode and update cart calculation', function () {
    $product = Product::factory()->create([
        'name' => 'USB Scanner',
        'sku' => 'SKU-SCAN-01',
        'barcode' => '1234567890128',
        'price' => 50.00,
    ]);

    Livewire::test(CashierCheckout::class)
        ->call('scanProduct', 'SKU-SCAN-01')
        ->assertSee('USB Scanner')
        ->assertSet('cart.0.sku', 'SKU-SCAN-01')
        ->assertSet('cart.0.quantity', 1)
        ->assertSet('subtotal', 50.00)
        ->assertSet('taxAmount', 4.00)
        ->assertSet('total', 54.00);
});

it('increments quantity when the same product is scanned multiple times', function () {
    $product = Product::factory()->create([
        'sku' => 'SKU-MULTI-01',
        'price' => 20.00,
    ]);

    Livewire::test(CashierCheckout::class)
        ->call('scanProduct', 'SKU-MULTI-01')
        ->call('scanProduct', 'SKU-MULTI-01')
        ->assertSet('cart.0.quantity', 2)
        ->assertSet('cart.0.subtotal', 40.00)
        ->assertSet('subtotal', 40.00);
});

it('can complete checkout and persist order with order items', function () {
    $product = Product::factory()->create([
        'sku' => 'SKU-ORDER-01',
        'name' => 'Test Item',
        'price' => 100.00,
    ]);

    Livewire::test(CashierCheckout::class)
        ->set('customerCode', 'MEMBER-888')
        ->set('paymentMethod', 'card')
        ->call('scanProduct', 'SKU-ORDER-01')
        ->call('completeCheckout')
        ->assertSet('cart', []);

    expect(Order::where('customer_code', 'MEMBER-888')->exists())->toBeTrue();
    $order = Order::where('customer_code', 'MEMBER-888')->first();
    expect($order->payment_method)->toBe('card')
        ->and($order->total_amount)->toEqual(108.00);

    expect(OrderItem::where('order_id', $order->id)->count())->toBe(1);
});
