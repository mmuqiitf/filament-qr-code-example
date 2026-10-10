<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Support\QrPayload;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Schema $form
 */
class QrCustomizer extends Page
{
    protected string $view = 'filament.pages.qr-customizer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Interactive QR Customizer';

    protected static ?string $navigationLabel = 'QR Customizer';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'content' => 'https://filamentphp.com',
            'color' => '#0f172a',
            'backgroundColor' => '#ffffff',
            'size' => 260,
            'margin' => 2,
            'errorCorrection' => 'M',
            'format' => 'svg',
            'caption' => 'Filament v5 QR Demo',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('QR Code Configuration')
                    ->description('Customize the visual appearance, encoding density, text overlays, and export format in real-time. Use the payload presets above to build WiFi/vCard/mailto/SMS/geo strings without hand-escaping.')
                    ->schema([
                        TextInput::make('content')
                            ->label('Data / Content to Encode')
                            ->required()
                            ->live(debounce: 300)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                ColorPicker::make('color')
                                    ->label('Foreground Color')
                                    ->live(debounce: 200),

                                ColorPicker::make('backgroundColor')
                                    ->label('Background Color')
                                    ->live(debounce: 200),

                                Select::make('format')
                                    ->label('Render Format')
                                    ->options([
                                        'svg' => 'SVG (Vector - Infinite Scale)',
                                        'png' => 'PNG (Raster Bitmap)',
                                    ])
                                    ->live(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('size')
                                    ->label('Size (px)')
                                    ->options([
                                        180 => '180px - Compact',
                                        240 => '240px - Standard',
                                        300 => '300px - Large',
                                        400 => '400px - High Resolution',
                                    ])
                                    ->live(),

                                Select::make('margin')
                                    ->label('Quiet Zone Margin')
                                    ->options([
                                        0 => '0 - No Margin',
                                        1 => '1 - Thin',
                                        2 => '2 - Standard',
                                        4 => '4 - Wide Margin',
                                    ])
                                    ->live(),

                                Select::make('errorCorrection')
                                    ->label('Error Correction Level')
                                    ->options([
                                        'L' => 'L (7% recovery)',
                                        'M' => 'M (15% recovery)',
                                        'Q' => 'Q (25% recovery)',
                                        'H' => 'H (30% recovery / Logo ready)',
                                    ])
                                    ->live(),
                            ]),

                        TextInput::make('caption')
                            ->label('Bottom Text Overlay (PNG export)')
                            ->placeholder('e.g. Scan to Verify')
                            ->live(debounce: 300)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Fill the content box with a QrPayload builder so escaping-sensitive
     * strings (WiFi/vCard/mailto/SMS/geo) never get hand-concatenated.
     */
    public function applyPayloadPreset(string $preset): void
    {
        $content = match ($preset) {
            'wifi' => QrPayload::wifi('Shop Floor', 'secret-1'),
            'vcard' => QrPayload::vcard([
                'firstName' => 'Siti',
                'lastName' => 'Ops',
                'organization' => 'Warehouse 7',
                'phone' => '+621234567',
                'email' => 'ops@example.com',
            ]),
            'mailto' => QrPayload::mailto('ops@example.com', 'Stock alert', 'Bin 12 empty'),
            'sms' => QrPayload::sms('+621234567', 'Arrived'),
            'geo' => QrPayload::geo(-6.2, 106.8, 'Warehouse 7'),
            default => 'https://filamentphp.com',
        };

        $this->data['content'] = $content;
        $this->form->fill($this->data);
    }

    public function getPreviewDataUriProperty(): string
    {
        $content = (string) ($this->data['content'] ?? 'https://example.com');
        if (trim($content) === '') {
            $content = 'https://example.com';
        }

        $color = (string) ($this->data['color'] ?? '#000000');
        $bgColor = (string) ($this->data['backgroundColor'] ?? '#ffffff');
        $size = (int) ($this->data['size'] ?? 240);
        $margin = (int) ($this->data['margin'] ?? 2);
        $ec = (string) ($this->data['errorCorrection'] ?? 'M');
        $format = ($this->data['format'] ?? 'svg') === 'png' ? QrFormat::Png : QrFormat::Svg;
        $caption = trim((string) ($this->data['caption'] ?? ''));

        try {
            $service = FilamentQrCode::make()
                ->format($format)
                ->size($size)
                ->margin($margin)
                ->color($color)
                ->backgroundColor($bgColor)
                ->errorCorrection($ec);

            if ($caption !== '' && $format === QrFormat::Png) {
                $service->withText($caption, 14, $color);
            }

            return $service->generate($content)->toDataUri();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function download(): StreamedResponse
    {
        $content = (string) ($this->data['content'] ?? 'qrcode');
        $color = (string) ($this->data['color'] ?? '#000000');
        $bgColor = (string) ($this->data['backgroundColor'] ?? '#ffffff');
        $size = (int) ($this->data['size'] ?? 300);
        $margin = (int) ($this->data['margin'] ?? 2);
        $ec = (string) ($this->data['errorCorrection'] ?? 'M');
        $format = ($this->data['format'] ?? 'svg') === 'png' ? QrFormat::Png : QrFormat::Svg;
        $caption = trim((string) ($this->data['caption'] ?? ''));

        $service = FilamentQrCode::make()
            ->format($format)
            ->size($size)
            ->margin($margin)
            ->color($color)
            ->backgroundColor($bgColor)
            ->errorCorrection($ec);

        if ($caption !== '' && $format === QrFormat::Png) {
            $service->withText($caption, 14, $color);
        }

        return $service->generate($content)->download('custom-qr-code');
    }
}
