<?php

namespace App\Filament\Pages;

use App\Models\Product;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * Cycle count with quantity tracking, CSV import/export, and a per-batch item cap:
 * rescans increment quantity instead of rejecting duplicates, operators can adjust
 * counts inline or import/export CSV rows, and maxItems caps distinct SKUs per batch.
 *
 * @property Schema $form
 */
class CycleCount extends Page
{
    protected string $view = 'filament.pages.cycle-count';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Cycle Count';

    protected static ?string $navigationLabel = 'Cycle Count';

    protected static ?string $slug = 'cycle-count';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var list<array{location: string, sku_count: int, total_units: int, items: list<array{code: string, qty: int}>}>
     */
    public array $recordedCounts = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'cycleCount';
    }

    public function mount(): void
    {
        $this->form->fill([
            'location' => 'Aisle A',
            'items' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('location')
                    ->options(['Aisle A' => 'Aisle A', 'Aisle B' => 'Aisle B', 'Back store' => 'Back store'])
                    ->required()
                    ->native(false),
                QrCollector::make('items')
                    ->label('Counted SKUs (max 5 lines)')
                    ->quantity()
                    ->maxItems(5)
                    ->csv(fileName: 'cycle-count.csv')
                    ->normalizeWith(uppercase: true)
                    ->itemRules(['regex:/^PRD-\d+$/'])
                    ->required()
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $items = is_array($value) ? $value : [];
                        $codes = array_values(array_unique(array_filter(array_map(
                            fn (mixed $item): string => is_array($item) && isset($item['code']) ? strtoupper((string) $item['code']) : '',
                            $items,
                        ))));

                        if ($codes === []) {
                            return;
                        }

                        $known = Product::query()->whereIn('sku', $codes)->pluck('sku')->all();
                        $unknown = array_diff($codes, $known);

                        if ($unknown !== []) {
                            $fail('Unknown SKU(s): '.implode(', ', $unknown).'.');
                        }
                    })
                    ->delayBetweenScans(300)
                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
                    ->preferRearCamera()
                    ->extraAttributes(['x-on:cycle-count-recorded.window' => 'clearAll()']),
            ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        /** @var list<array{code: string, qty: int}> $items */
        $items = array_values(array_map(
            fn (array $row): array => [
                'code' => strtoupper((string) $row['code']),
                'qty' => max(1, (int) $row['qty']),
            ],
            $state['items'],
        ));

        $totalUnits = array_sum(array_column($items, 'qty'));

        array_unshift($this->recordedCounts, [
            'location' => (string) $state['location'],
            'sku_count' => count($items),
            'total_units' => $totalUnits,
            'items' => $items,
        ]);

        Notification::make()
            ->title("{$state['location']} cycle count saved")
            ->body(count($items)." SKU(s), {$totalUnits} total unit(s).")
            ->success()
            ->send();

        $this->form->fill(['location' => $state['location'], 'items' => []]);
        $this->dispatch('cycle-count-recorded');
    }
}
