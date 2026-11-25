<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

/**
 * Uncompressed GS1 Digital Link: build a URI, encode it as a QR, draw the
 * same AIs as GS1 DataMatrix, and parse a URI back to a bracketed string.
 *
 * @property Schema $form
 */
class DigitalLinkShowcase extends Page
{
    public const GTIN = '08901234567074';

    public const LOT = 'L2611A';

    protected string $view = 'filament.pages.digital-link-showcase';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static \UnitEnum|string|null $navigationGroup = 'Generators & Display';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'GS1 Digital Link';

    protected static ?string $navigationLabel = 'Digital Link';

    protected static ?string $slug = 'digital-link';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'digitalLink';
    }

    /**
     * @return array<int|string, string>
     */
    public static function elements(): array
    {
        return ['01' => self::GTIN, '10' => self::LOT];
    }

    public static function uri(): string
    {
        return DigitalLink::build(self::elements());
    }

    public static function bracketed(): string
    {
        return Gs1::build(self::elements())->bracketed();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $uri = self::uri();
        $bracketed = self::bracketed();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('URI and QR')
                    ->description('DigitalLink::build() writes an uncompressed path+query URI. QrCodeDisplay encodes that URI, so a scan of the QR is accepted by QrScanner::gs1().')
                    ->schema([
                        Text::make($uri)->fontFamily(FontFamily::Mono)->copyable(),
                        QrCodeDisplay::make('digital_link_qr')
                            ->hiddenLabel()
                            ->data($uri)
                            ->size(220)
                            ->downloadable()
                            ->dehydrated(false),
                    ]),
                Section::make('Same AIs as GS1 DataMatrix')
                    ->description('The element string for the same GTIN and lot, drawn as GS1 DataMatrix. Print labels on the Products table combine this 2D symbol with a Digital Link QR.')
                    ->schema([
                        Text::make($bracketed)->fontFamily(FontFamily::Mono)->copyable(),
                        BarcodeDisplay::make('digital_link_dm')
                            ->hiddenLabel()
                            ->type(BarcodeType::Gs1DataMatrix)
                            ->data($bracketed)
                            ->downloadable()
                            ->dehydrated(false),
                    ]),
                Section::make('Parse a URI')
                    ->description('DigitalLink::parse() and Gs1::parse() both read the URI into the same ElementString. The stored scan is the bracketed form.')
                    ->schema([
                        Text::make(DigitalLink::parse($uri)->bracketed())->fontFamily(FontFamily::Mono)->copyable(),
                    ]),
            ]);
    }
}
