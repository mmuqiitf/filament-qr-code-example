<?php

namespace App\Filament\Pages;

use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Enums\ContinuousDisplay;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * POS line entry: continuous, page-wide scanning with a Product lookup.
 * Every accepted scan adds a cart line; the same SKU again bumps the quantity
 * (merging lines is the app's job, done here in addToCart()).
 * The option switches change the scanner in place: its options follow the
 * server on every re-render, with no wire:key re-mount (P2.1).
 *
 * @property Schema $form
 */
class PosCheckout extends Page
{
    protected string $view = 'filament.pages.pos-checkout';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'POS Checkout';

    protected static ?string $navigationLabel = 'POS Checkout';

    protected static ?string $slug = 'pos-checkout';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Cart lines keyed by SKU.
     *
     * @var array<string, array{sku: string, name: string, price: float, quantity: int}>
     */
    public array $cart = [];

    public bool $keepLastCode = false;

    public bool $pauseOnReject = false;

    public bool $keepCameraOpen = false;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'posCheckout';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrScanner::make('scan')
                    ->label('Scan item')
                    ->mode(ScanMode::Continuous)
                    ->hardwareInput(scope: HardwareInputScope::Page)
                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128, BarcodeFormat::Ean13])
                    // Handhelds set up for lower case still match: cleaned in the browser and on the server.
                    ->normalizeWith(uppercase: true)
                    ->lookup(Product::class, 'sku')
                    ->onScanned(fn (Product $record) => $this->addToCart($record))
                    ->continuousDisplay(fn (): ContinuousDisplay => $this->keepLastCode ? ContinuousDisplay::LastCode : ContinuousDisplay::Empty)
                    ->pauseOnReject(fn (): bool => $this->pauseOnReject)
                    ->keepCameraOpen(fn (): bool => $this->keepCameraOpen)
                    ->beep(success: true, error: true)
                    ->flash()
                    ->dehydrated(false)
                    ->placeholder('Scan PRD-1001...'),
            ]);
    }

    public function addToCart(Product $product): void
    {
        $sku = (string) $product->sku;

        if (isset($this->cart[$sku])) {
            $this->cart[$sku]['quantity']++;

            return;
        }

        $this->cart[$sku] = [
            'sku' => $sku,
            'name' => (string) $product->name,
            'price' => (float) $product->price,
            'quantity' => 1,
        ];
    }

    public function removeLine(string $sku): void
    {
        unset($this->cart[$sku]);
    }

    public function getTotal(): float
    {
        return array_sum(array_map(fn (array $line): float => $line['price'] * $line['quantity'], $this->cart));
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCart')
                ->label('Clear cart')
                ->color('gray')
                ->action(fn () => $this->cart = []),
        ];
    }
}
