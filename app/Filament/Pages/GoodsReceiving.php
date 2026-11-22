<?php

namespace App\Filament\Pages;

use App\Models\GoodsReceipt;
use App\Models\Product;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LogicException;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

/**
 * Goods receiving with a handheld scanner: a sticky purchase order, a SKU
 * looked up in Products, an optional lot and one serial per unit. An
 * unlabelled item skips the lot: its first serial lands in the serials step.
 * The whole item is checked once complete and received automatically.
 *
 * @property Schema $form
 */
class GoodsReceiving extends Page
{
    protected string $view = 'filament.pages.goods-receiving';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Goods Receiving';

    protected static ?string $navigationLabel = 'Goods Receiving';

    protected static ?string $slug = 'goods-receiving';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public bool $autoSubmit = true;

    public int $quantity = 2;

    /**
     * Units locked when the SKU is accepted. Null until then, so the input
     * still changes how many serials the next item asks for.
     */
    public ?int $units = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'goodsReceiving';
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
                QrScanSequence::make()
                    ->statePath('receipt')
                    ->steps([
                        ScanStep::make('po')
                            ->label('Purchase order')
                            ->validateUsing(['regex:/^PO-\d+$/'])
                            ->sticky(),
                        ScanStep::make('sku')
                            ->label('SKU')
                            ->lookup(Product::class, 'sku'),
                        ScanStep::make('lot')
                            ->label('Lot (if labelled)')
                            ->validateUsing(['regex:/^LOT-[A-Z0-9]+$/'])
                            // Optional: a serial scanned here gives way to the serials step.
                            ->optional(),
                        ScanStep::make('serials')
                            ->label('Serial numbers')
                            ->validateUsing(['regex:/^SN-[A-Z0-9-]+$/'])
                            ->repeat(fn (): int => $this->unitsForItem()),
                    ])
                    ->onStepAccepted(function (string $step): void {
                        if ($step === 'sku') {
                            $this->units = $this->unitsForItem();
                        }
                    })
                    ->validateAllUsing(function (array $values): array {
                        $prefix = 'SN-'.($values['sku'] ?? '').'-';

                        foreach ($values['serials'] ?? [] as $serial) {
                            if (! str_starts_with((string) $serial, $prefix)) {
                                return ['serials' => "Serials must belong to {$values['sku']}."];
                            }
                        }

                        return [];
                    })
                    ->autoSubmit(fn (): bool => $this->autoSubmit)
                    ->resetAfterSubmit()
                    ->editable(false)
                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128, BarcodeFormat::Code39])
                    ->preferRearCamera(),
            ]);
    }

    public function submit(): void
    {
        // getState() re-runs every step check and the whole-set check.
        $receipt = $this->form->getState()['receipt'] ?? [];

        if (blank($receipt['po'] ?? null) || blank($receipt['sku'] ?? null) || count($receipt['serials'] ?? []) < $this->unitsForItem()) {
            Notification::make()
                ->title('Incomplete item')
                ->body('Scan the PO, the SKU and every serial before receiving.')
                ->warning()
                ->send();

            return;
        }

        $product = Product::query()->where('sku', $receipt['sku'])->firstOrFail();

        GoodsReceipt::create([
            'po_number' => $receipt['po'],
            'product_id' => $product->getKey(),
            'sku' => $product->sku,
            'lot_number' => $receipt['lot'] ?? null,
            'serials' => array_values($receipt['serials']),
        ]);

        Notification::make()
            ->title("Received {$product->name}")
            ->body(count($receipt['serials'])." unit(s) on {$receipt['po']}.")
            ->success()
            ->send();

        // The next item starts unlocked. Clear before the sequence reset so a
        // state write cannot freeze the count that just finished.
        $this->units = null;

        // Start the next item, keeping the sticky PO (same as the sequence's own reset).
        $this->sequence()->resetSequence();
    }

    public function updatedQuantity(): void
    {
        $clamped = max(1, min(20, (int) $this->quantity));
        $next = $this->units ?? $clamped;

        if ($this->quantity !== $next) {
            $this->quantity = $next;
        }
    }

    public function updatedData(): void
    {
        $receipt = $this->data['receipt'] ?? [];
        $serials = $receipt['serials'] ?? null;

        if (blank($receipt['sku'] ?? null) && blank($serials)) {
            $this->units = null;
        }
    }

    public function unitsForItem(): int
    {
        return $this->units ?? max(1, min(20, $this->quantity));
    }

    protected function sequence(): QrScanSequence
    {
        $sequence = $this->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);

        if (! $sequence instanceof QrScanSequence) {
            throw new LogicException('The form has no QrScanSequence.');
        }

        return $sequence;
    }
}
