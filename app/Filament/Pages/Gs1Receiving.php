<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\ReceivedLine;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Tables\Columns\BarcodeColumn;

/**
 * Receiving against GS1 logistic labels: one scan of a GS1-128 (or GS1
 * DataMatrix / GS1 QR) label fills the GTIN, expiry, batch, serial, net
 * weight and count. The package rejects labels that are not valid GS1 or
 * lack a GTIN and expiry; the page then rejects expired stock and GTINs that
 * are not in Products. Received lines are listed with their label redrawn as
 * GS1-128.
 *
 * @property Schema $form
 */
class Gs1Receiving extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.gs1-receiving';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'GS1 Receiving';

    protected static ?string $navigationLabel = 'GS1 Receiving';

    protected static ?string $slug = 'gs1-receiving';

    /**
     * AI => field the scan fills.
     */
    public const FILL = [
        '01' => 'gtin',
        '10' => 'batch',
        '17' => 'expires_at',
        '21' => 'serial',
        '3103' => 'net_weight_kg',
        '30' => 'quantity',
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'gs1Receiving';
    }

    public function mount(): void
    {
        $this->form->fill(['quantity' => 1]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Scan the pallet or case label')
                    ->description('Shoot the GS1-128 label with a handheld scanner (no need to click the field) or use the camera. The label must carry a GTIN (01) and an expiry date (17).')
                    ->schema([
                        QrScanner::make('label')
                            ->label('GS1 label')
                            // A closure: PHP turns keys like '10' into ints, which gs1()'s array<string, string> PHPDoc refuses.
                            ->gs1(fill: fn (): array => self::FILL, require: ['01', '17'])
                            // Later checks see the bracketed string, so parse it again for the AIs.
                            ->validateUsing(function (string $code): ?string {
                                $expiry = Gs1::parse($code)->date('17');

                                return $expiry !== null && $expiry->lt(today())
                                    ? "Expired on {$expiry->toDateString()}. Quarantine the stock instead of receiving it."
                                    : null;
                            })
                            ->validateUsing(function (string $code): ?string {
                                $gtin = Gs1::parse($code)->get('01');

                                return Product::forGtin($gtin) === null
                                    ? "GTIN {$gtin} is not in the product catalogue."
                                    : null;
                            })
                            // Runs for scans and pasted labels, after the package's GS1 fill.
                            ->afterStateUpdated(fn (?string $state, Set $set) => $this->syncLine($state, $set))
                            ->onRejected(function (ScanResult $result, Set $set): void {
                                $this->syncLine(null, $set);
                                $this->addError('data.label', (string) $result->message);
                            })
                            ->hardwareInput(scope: HardwareInputScope::Page)
                            ->formats([BarcodeFormat::Code128, BarcodeFormat::DataMatrix, BarcodeFormat::QrCode])
                            ->beep(success: true, error: true)
                            ->preferRearCamera()
                            ->required()
                            ->placeholder('Scan a GS1 label…'),

                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            TextInput::make('product')
                                ->label('Product')
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('gtin')
                                ->label('GTIN (01)')
                                ->readOnly()
                                ->required(),
                            DatePicker::make('expires_at')
                                ->label('Expiry (17)')
                                ->readOnly()
                                ->required(),
                            TextInput::make('batch')
                                ->label('Batch / lot (10)')
                                ->readOnly(),
                            TextInput::make('serial')
                                ->label('Serial (21)')
                                ->readOnly(),
                            TextInput::make('net_weight_kg')
                                ->label('Net weight (3103)')
                                ->suffix('kg')
                                ->readOnly(),
                            TextInput::make('quantity')
                                ->label('Count (30)')
                                ->helperText('From AI (30) when the label has one.')
                                ->integer()
                                ->minValue(1)
                                ->required(),
                        ]),
                    ]),
            ]);
    }

    /**
     * Keep the line in step with the label: the package fills the AIs a label
     * carries and leaves the others as they were, so clear the fields this
     * label does not carry (nothing from an earlier label may be received
     * with it) and show the product. A null label clears the whole line.
     */
    protected function syncLine(?string $label, Set $set): void
    {
        $this->resetErrorBag('data.label');

        $elements = filled($label) ? Gs1::parse($label) : null;

        foreach (self::FILL as $ai => $field) {
            if ($elements?->get((string) $ai) === null) {
                $set($field, $field === 'quantity' ? 1 : null);
            }
        }

        $set('product', Product::forGtin($elements?->get('01'))?->name);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ReceivedLine::query()->with('product'))
            ->heading('Received lines')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product'),
                BarcodeColumn::make('label')
                    ->type(BarcodeType::Gs1128),
                TextColumn::make('batch')
                    ->placeholder('-'),
                TextColumn::make('serial')
                    ->placeholder('-'),
                TextColumn::make('expires_at')
                    ->label('Expiry')
                    ->date(),
                TextColumn::make('net_weight_kg')
                    ->label('Net kg')
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->label('Count'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since(),
            ])
            ->emptyStateHeading('Nothing received yet')
            ->emptyStateDescription('Scan a label from the GS1 Labels page.');
    }

    public function receive(): void
    {
        // getState() runs the GS1 and page checks again on the stored label.
        $data = $this->form->getState();

        $product = Product::forGtin(Gs1::parse($data['label'])->get('01'));

        if ($product === null) {
            $this->addError('data.label', 'The label\'s GTIN is not in the product catalogue.');

            return;
        }

        $line = ReceivedLine::create([
            'product_id' => $product->getKey(),
            'label' => $data['label'],
            'gtin' => $data['gtin'],
            'batch' => $data['batch'] ?? null,
            'serial' => $data['serial'] ?? null,
            'expires_at' => $data['expires_at'],
            'net_weight_kg' => $data['net_weight_kg'] ?? null,
            'quantity' => (int) $data['quantity'],
        ]);

        Notification::make()
            ->title("Received {$line->quantity} × {$product->name}")
            ->body("Expires {$data['expires_at']}".($line->batch ? ", batch {$line->batch}" : '').'.')
            ->success()
            ->send();

        $this->form->fill(['quantity' => 1]);
    }
}
