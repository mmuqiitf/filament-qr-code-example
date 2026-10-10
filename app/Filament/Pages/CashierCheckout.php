<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

/**
 * @property Schema $form
 */
class CashierCheckout extends Page
{
    protected string $view = 'filament.pages.cashier-checkout';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Cashier POS Checkout';

    protected static ?string $navigationLabel = 'Cashier Checkout';

    public string $cashierBadge = 'CASHIER-01';

    public string $customerCode = '';

    public string $scanInput = '';

    public string $paymentMethod = 'cash';

    /**
     * @var array<int, array{
     *     product_id: int|null,
     *     sku: string,
     *     name: string,
     *     price: float,
     *     quantity: int,
     *     subtotal: float
     * }>
     */
    public array $cart = [];

    public float $taxRate = 0.08;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->cart = [];
        $this->cashierBadge = 'CSH-'.strtoupper(Str::random(4));
        $this->form->fill();
    }

    /**
     * Cashiers scan with a handheld gun, so there is no camera UI here —
     * just the invisible hardware scanner interceptor catching bursts anywhere on
     * the page and routing them into the SKU box. Terminator-less guns flush
     * after scanTimeoutMs (default 150); field QrScanner listeners stand down
     * while this page-global listener is mounted.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrHardwareScannerListener::make([
                    'scanInput',
                ])
                    ->autoFocusNext(false)
                    ->sound(true)
                    ->hardwareScanner(terminators: ['Enter', 'Tab'], minBarcodeLength: 2, burstThresholdMs: 50),
            ]);
    }

    public function handleManualScan(): void
    {
        if (filled($this->scanInput)) {
            $this->scanProduct($this->scanInput);
            $this->scanInput = '';
        }
    }

    public function scanProduct(string $code): void
    {
        // Mirror the JS interceptor: strip gun framing (STX/ETX/CR/LF) and whitespace.
        $cleaned = HasHardwareScanner::sanitizeScannedValue($code);
        if ($cleaned === '') {
            return;
        }

        // Server-observed scan: enters the QrCodeScanned audit trail (logged when
        // qr-code.audit.enabled). Live bursts stay client-side until they reach us here.
        event(new QrCodeScanned(code: $cleaned, source: 'cashier-pos', field: 'scanInput'));

        $product = Product::query()
            ->where('sku', $cleaned)
            ->orWhere('barcode', $cleaned)
            ->first();

        if (! $product) {
            Notification::make()
                ->title('Product Not Found')
                ->body("No catalog item matches barcode/SKU [{$cleaned}].")
                ->danger()
                ->send();

            return;
        }

        // Check if already in cart
        $found = false;
        foreach ($this->cart as $index => $item) {
            if ($item['sku'] === $product->sku) {
                $this->cart[$index]['quantity']++;
                $this->cart[$index]['subtotal'] = round($this->cart[$index]['quantity'] * $this->cart[$index]['price'], 2);
                $found = true;
                break;
            }
        }

        if (! $found) {
            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
                'subtotal' => (float) $product->price,
            ];
        }

        Notification::make()
            ->title("Added {$product->name}")
            ->body("SKU: {$product->sku} | Price: \${$product->price}")
            ->success()
            ->duration(2000)
            ->send();
    }

    public function incrementQuantity(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity']++;
            $this->cart[$index]['subtotal'] = round($this->cart[$index]['quantity'] * $this->cart[$index]['price'], 2);
        }
    }

    public function decrementQuantity(int $index): void
    {
        if (isset($this->cart[$index])) {
            if ($this->cart[$index]['quantity'] > 1) {
                $this->cart[$index]['quantity']--;
                $this->cart[$index]['subtotal'] = round($this->cart[$index]['quantity'] * $this->cart[$index]['price'], 2);
            } else {
                $this->removeItem($index);
            }
        }
    }

    public function removeItem(int $index): void
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function getSubtotalProperty(): float
    {
        return (float) array_sum(array_column($this->cart, 'subtotal'));
    }

    public function getTaxAmountProperty(): float
    {
        return round($this->getSubtotalProperty() * $this->taxRate, 2);
    }

    public function getTotalProperty(): float
    {
        return round($this->getSubtotalProperty() + $this->getTaxAmountProperty(), 2);
    }

    public function completeCheckout(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Cart is empty')
                ->body('Scan at least one product before proceeding with checkout.')
                ->warning()
                ->send();

            return;
        }

        $orderNumber = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

        $order = Order::create([
            'order_number' => $orderNumber,
            'cashier_badge' => $this->cashierBadge,
            'customer_code' => $this->customerCode ?: null,
            'total_amount' => $this->getTotalProperty(),
            'payment_method' => $this->paymentMethod,
            'status' => 'completed',
        ]);

        foreach ($this->cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'sku' => $item['sku'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        Notification::make()
            ->title('Order Completed Successfully!')
            ->body("Order #{$orderNumber} registered with total \${$this->getTotalProperty()}.")
            ->success()
            ->send();

        // Reset for next customer
        $this->cart = [];
        $this->customerCode = '';
    }
}
