<?php

namespace App\Filament\Pages;

use App\Support\Gs1SampleLabels;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;

/**
 * GS1-128 sample labels to scan from the screen into GS1 Receiving: three
 * that are received and four that are rejected, each with the reason the
 * receiving page gives. Dates are relative to today.
 *
 * @property Schema $form
 */
class Gs1Labels extends Page
{
    protected string $view = 'filament.pages.gs1-labels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static \UnitEnum|string|null $navigationGroup = 'Generators & Display';

    protected static ?string $title = 'GS1 Labels';

    protected static ?string $navigationLabel = 'GS1 Labels';

    protected static ?string $slug = 'gs1-labels';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'gs1Labels';
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
                Grid::make(['default' => 1, 'lg' => 2])->schema(
                    array_map(fn (array $sample): Section => $this->sampleSection($sample), Gs1SampleLabels::all()),
                ),
            ]);
    }

    /**
     * @param  array{key: string, title: string, caption: string, value: string, gtin: ?string, symbology: string, expected: ?string}  $sample
     */
    protected function sampleSection(array $sample): Section
    {
        $components = [
            BarcodeDisplay::make("label_{$sample['key']}")
                ->hiddenLabel()
                ->type($sample['symbology'])
                ->data($sample['value'])
                ->barWidth(2)
                ->height(80)
                ->downloadable()
                ->dehydrated(false),
        ];

        if ($sample['symbology'] !== BarcodeType::Gs1128->value && str_starts_with($sample['value'], '(')) {
            // The same value as GS1-128: the package refuses to draw it and says why.
            $components[] = BarcodeDisplay::make("label_{$sample['key']}_gs1")
                ->label('As GS1-128')
                ->type(BarcodeType::Gs1128)
                ->data($sample['value'])
                ->dehydrated(false);
        }

        $components[] = Text::make($sample['value'])->fontFamily(FontFamily::Mono)->copyable();
        $components[] = Text::make($sample['expected'] === null ? 'Scans as: received' : "Scans as: rejected, \"{$sample['expected']}\"")
            ->color($sample['expected'] === null ? 'success' : 'danger');

        return Section::make($sample['title'])
            ->description($sample['caption'])
            ->icon($sample['expected'] === null ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->iconColor($sample['expected'] === null ? 'success' : 'danger')
            ->schema($components);
    }
}
