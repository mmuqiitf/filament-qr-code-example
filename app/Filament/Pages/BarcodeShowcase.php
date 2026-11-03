<?php

namespace App\Filament\Pages;

use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;

/**
 * The package's 1D barcode components: one sample per symbology, a try-it
 * field that shows the inline error for an invalid value, and the product
 * barcodes as Code 128 labels.
 *
 * @property Schema $form
 */
class BarcodeShowcase extends Page
{
    protected string $view = 'filament.pages.barcode-showcase';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Generators & Display';

    protected static ?string $title = 'Barcode Showcase';

    protected static ?string $navigationLabel = 'Barcodes';

    protected static ?string $slug = 'barcode-showcase';

    /**
     * Valid sample values, one per symbology (check digits included).
     */
    public const SAMPLES = [
        'code128' => 'PRD-1001',
        'ean13' => '5901234123457',
        'ean8' => '96385074',
        'upca' => '036000291452',
        'code39' => 'LOT-A1',
        'itf14' => '15400141288763',
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'barcodeShowcase';
    }

    public function mount(): void
    {
        $this->form->fill(['format' => 'svg', 'type' => 'ean13', 'value' => self::SAMPLES['ean13']]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Symbologies')
                    ->description('One valid sample per supported type. Download any of them as SVG or PNG.')
                    ->schema([
                        Select::make('format')
                            ->label('Image format')
                            ->options(['svg' => 'SVG', 'png' => 'PNG'])
                            ->selectablePlaceholder(false)
                            ->live(),
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema(
                            array_map(fn (BarcodeType $type): BarcodeDisplay => BarcodeDisplay::make("sample_{$type->value}")
                                ->label($type->getLabel())
                                ->type($type)
                                ->data(self::SAMPLES[$type->value])
                                ->format(fn (): string => $this->format())
                                ->dehydrated(false), BarcodeType::cases()),
                        ),
                    ]),

                Section::make('Try it')
                    ->description('Invalid values (wrong length, bad check digit, unsupported characters) show an inline error.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('type')
                                ->options(collect(BarcodeType::cases())->mapWithKeys(fn (BarcodeType $type): array => [$type->value => $type->getLabel()]))
                                ->selectablePlaceholder(false)
                                ->live(),
                            TextInput::make('value')
                                ->live(onBlur: true),
                        ]),
                        BarcodeDisplay::make('preview')
                            ->hiddenLabel()
                            ->type(fn (): string => (string) ($this->data['type'] ?? 'code128'))
                            ->data(fn (): ?string => filled($this->data['value'] ?? null) ? (string) $this->data['value'] : null)
                            ->format(fn (): string => $this->format())
                            ->dehydrated(false),
                    ]),

                Section::make('Product barcodes')
                    ->description('Stored product barcodes rendered as Code 128, which accepts any printable ASCII value.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema(
                            fn (): array => Product::query()->orderBy('sku')->get()
                                ->filter(fn (Product $product): bool => filled($product->barcode))
                                ->map(fn (Product $product): BarcodeDisplay => BarcodeDisplay::make("product_{$product->getKey()}")
                                    ->label($product->name)
                                    ->type(BarcodeType::Code128)
                                    ->data($product->barcode)
                                    ->format(fn (): string => $this->format())
                                    ->dehydrated(false))
                                ->values()
                                ->all(),
                        ),
                    ]),
            ]);
    }

    protected function format(): string
    {
        return ($this->data['format'] ?? 'svg') === 'png' ? 'png' : 'svg';
    }
}
