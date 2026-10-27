<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\StockCount;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * Shelf audit with the batch collector: scan every product label on a shelf
 * (camera or handheld), each code is looked up as it lands, duplicates are
 * refused in the browser and on submit, and the count is recorded with the
 * products that were not seen.
 *
 * @property Schema $form
 */
class ShelfAudit extends Page
{
    protected string $view = 'filament.pages.shelf-audit';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Shelf Audit';

    protected static ?string $navigationLabel = 'Shelf Audit';

    protected static ?string $slug = 'shelf-audit';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'shelfAudit';
    }

    public function mount(): void
    {
        $this->form->fill(['location' => 'Aisle A']);
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
                QrCollector::make('codes')
                    ->label('Product labels')
                    ->allowDuplicates(false)
                    ->distinctItems()
                    ->required()
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $codes = array_map('strval', (array) $value);
                        $unknown = array_diff($codes, Product::query()->whereIn('sku', $codes)->pluck('sku')->all());

                        if ($unknown !== []) {
                            $fail('Unknown code(s): '.implode(', ', $unknown).'. Remove them before submitting.');
                        }
                    })
                    ->delayBetweenScans(300)
                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
                    ->preferRearCamera()
                    // The browser keeps its own list: empty it once the count is recorded.
                    ->extraAttributes(['x-on:shelf-audit-recorded.window' => 'clearAll()']),
            ]);
    }

    /**
     * Called by the collector for every new code (the $wire channel).
     */
    public function handleCollectorScan(string $code): void
    {
        $product = Product::query()->where('sku', $code)->first();

        if ($product === null) {
            Notification::make()
                ->title("Unknown code {$code}")
                ->body('Remove it (×) before submitting.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($product->name)
            ->body("{$code} counted.")
            ->success()
            ->send();
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $counted = array_values($state['codes']);

        $missing = Product::query()
            ->whereNotIn('sku', $counted)
            ->orderBy('sku')
            ->pluck('sku')
            ->all();

        StockCount::create([
            'location' => $state['location'],
            'counted' => $counted,
            'missing' => $missing,
        ]);

        Notification::make()
            ->title("{$state['location']} counted")
            ->body(count($counted).' product(s) on the shelf, '.count($missing).' not seen.')
            ->success()
            ->send();

        $this->form->fill(['location' => $state['location'], 'codes' => []]);
        $this->dispatch('shelf-audit-recorded');
    }

    /**
     * @return Collection<int, StockCount>
     */
    public function getRecentCounts(): Collection
    {
        return StockCount::query()->latest('id')->limit(5)->get();
    }
}
