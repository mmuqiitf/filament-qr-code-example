<?php

namespace App\Filament\Pages;

use App\Models\AssemblyOrder;
use App\Models\Product;
use App\Support\BadgeDirectory;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * A QR generator page built in the app on the package's generator API (the
 * FilamentQrCode facade): printable label sheets for the codes the scanning
 * demos expect, plus one-label downloads as SVG or PNG.
 *
 * @property Schema $form
 */
class QrLabels extends Page
{
    protected string $view = 'filament.pages.qr-labels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static \UnitEnum|string|null $navigationGroup = 'Generators & Display';

    protected static ?string $title = 'QR Labels';

    protected static ?string $navigationLabel = 'QR Labels';

    protected static ?string $slug = 'qr-labels';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public const SETS = [
        'products' => 'Product SKUs (POS, receiving)',
        'work-orders' => 'Open work orders (manufacturing)',
        'components' => 'Component labels (manufacturing)',
        'badges' => 'Operator badges (manufacturing)',
        'station' => 'Station codes (DONE, PO, lot, rework)',
    ];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'qrLabels';
    }

    public function mount(): void
    {
        $this->form->fill(['set' => 'products', 'format' => 'svg', 'color' => '#0f172a', 'size' => 160]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(4)->schema([
                    Select::make('set')
                        ->label('Labels')
                        ->options(self::SETS)
                        ->selectablePlaceholder(false)
                        ->live(),
                    Select::make('format')
                        ->label('Download as')
                        ->options(['svg' => 'SVG', 'png' => 'PNG'])
                        ->selectablePlaceholder(false)
                        ->live(),
                    Select::make('size')
                        ->options([120 => 'Small', 160 => 'Medium', 220 => 'Large'])
                        ->selectablePlaceholder(false)
                        ->live(),
                    ColorPicker::make('color')
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                ]),
            ]);
    }

    /**
     * Validate the style fields (an invalid colour is reported, never drawn black).
     */
    public function applyStyle(): void
    {
        $this->form->getState();

        Notification::make()->title('Label style updated')->success()->send();
    }

    /**
     * @return list<array{code: string, caption: string}>
     */
    public function labels(): array
    {
        return match ($this->data['set'] ?? 'products') {
            'products' => Product::query()->orderBy('sku')->get()
                ->map(fn (Product $product): array => ['code' => (string) $product->sku, 'caption' => (string) $product->name])->all(),
            'work-orders' => AssemblyOrder::query()->where('status', 'open')->orderBy('number')->get()
                ->map(fn (AssemblyOrder $order): array => ['code' => $order->number, 'caption' => $order->product_name])->all(),
            'components' => $this->componentLabels(),
            'badges' => array_map(fn (array $operator): array => ['code' => $operator['badge'], 'caption' => $operator['name']], app(BadgeDirectory::class)->active()),
            'station' => [
                ['code' => 'DONE', 'caption' => 'Finish a repeat step'],
                ['code' => 'PO-7001', 'caption' => 'Purchase order'],
                ['code' => 'LOT-A1', 'caption' => 'Lot'],
                ['code' => 'RW-07', 'caption' => 'Rework reason'],
            ],
            default => [],
        };
    }

    /**
     * Inline preview through the generator API; SVG keeps the page light.
     */
    public function previewDataUri(string $code): string
    {
        try {
            return FilamentQrCode::make()
                ->format(QrFormat::Svg)
                ->size((int) ($this->data['size'] ?? 160))
                ->margin(1)
                ->color($this->validColor())
                ->generate($code)
                ->toDataUri();
        } catch (Throwable) {
            return '';
        }
    }

    public function download(string $code): ?StreamedResponse
    {
        // Only codes on the current sheet: the page is not a generic QR endpoint.
        if (! in_array($code, array_column($this->labels(), 'code'), true)) {
            return null;
        }

        return FilamentQrCode::make()
            ->format(($this->data['format'] ?? 'svg') === 'png' ? QrFormat::Png : QrFormat::Svg)
            ->size(400)
            ->margin(2)
            ->color($this->validColor())
            ->errorCorrection('M')
            ->generate($code)
            ->download('qr-'.$code);
    }

    /**
     * One label per BOM unit of every open work order: CMP-100:0001, CMP-100:0002, ...
     *
     * @return list<array{code: string, caption: string}>
     */
    protected function componentLabels(): array
    {
        $labels = [];

        foreach (AssemblyOrder::query()->where('status', 'open')->with('bomLines')->orderBy('number')->get() as $order) {
            foreach ($order->bom() as $sku => $quantity) {
                for ($unit = 1; $unit <= $quantity; $unit++) {
                    $labels[] = ['code' => sprintf('%s:%04d', $sku, $unit), 'caption' => "{$order->number} · {$sku}"];
                }
            }
        }

        return $labels;
    }

    protected function validColor(): string
    {
        $color = (string) ($this->data['color'] ?? '');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#0f172a';
    }
}
