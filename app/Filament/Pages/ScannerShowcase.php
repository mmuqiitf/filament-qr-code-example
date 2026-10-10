<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * Every customizable QrScanner scenario on one page for smoke testing.
 *
 * @property Schema $form
 */
class ScannerShowcase extends Page
{
    protected string $view = 'filament.pages.scanner-showcase';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Scanner Showcase';

    protected static ?string $navigationLabel = 'Scanner Showcase';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    #[On('qr-scan-rejected')]
    public function handleScanRejected(string $message): void
    {
        Notification::make()
            ->title('Scan rejected')
            ->body($message)
            ->warning()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('1. Basic scanner')
                    ->description('Defaults: every symbology (effective 12 fps auto-degrade when unrestricted), upload fallback, rear-camera preference. Served over https/localhost — cameras are blocked in insecure contexts.')
                    ->schema([
                        QrScanner::make('basic_sku')
                            ->label('SKU')
                            ->placeholder('Scan any code...'),
                    ]),

                Section::make('2. QR codes only')
                    ->description('Decoder attempts QR codes exclusively — faster with fewer false positives.')
                    ->schema([
                        QrScanner::make('qr_only')
                            ->label('QR payload')
                            ->formats([BarcodeFormat::QrCode])
                            ->placeholder('Scan a QR code...'),
                    ]),

                Section::make('3. Retail barcodes')
                    ->description('One-dimensional symbologies get a wide decode band automatically. Live values normalize via normalizeUsing(); scanFormat() stays programmatic-only.')
                    ->schema([
                        QrScanner::make('retail_barcode')
                            ->label('EAN / UPC / Code 128')
                            ->formats([
                                BarcodeFormat::Ean13,
                                BarcodeFormat::Ean8,
                                BarcodeFormat::UpcA,
                                BarcodeFormat::UpcE,
                                BarcodeFormat::Code128,
                            ])
                            ->scanFormat(fn (?string $rawValue): ?string => $rawValue ? trim($rawValue) : null)
                            ->normalizeUsing(fn ($rawValue) => filled($rawValue) ? trim((string) $rawValue) : $rawValue)
                            ->placeholder('Scan a retail barcode...'),
                    ]),

                Section::make('4. No upload fallback')
                    ->description('Camera or handheld scanner only — the image-file option is hidden.')
                    ->schema([
                        QrScanner::make('no_upload')
                            ->label('Strict capture')
                            ->allowUpload(false)
                            ->placeholder('Camera or handheld scanner only...'),
                    ]),

                Section::make('5. Custom feedback and burst tuning')
                    ->description('Low slow beep, long vibration, bursts shorter than 4 chars treated as typing. Buffers are sanitized (STX/ETX/CR/LF stripped); terminator-less guns flush after the scan timeout.')
                    ->schema([
                        QrScanner::make('custom_feedback')
                            ->label('Tuned scanner')
                            ->beepFrequency(520)
                            ->beepDuration(150)
                            ->vibrateDuration(300)
                            ->hardwareScanner(enabled: true, burstThresholdMs: 50, minBarcodeLength: 4, scanTimeoutMs: 150)
                            ->placeholder('Scan to hear the difference...'),
                    ]),

                Section::make('6. Focus handoff pair')
                    ->description('Scanning the first field jumps straight to the second (field handoff, not sequential scanning).')
                    ->schema([
                        QrScanner::make('handoff_a')
                            ->label('First stop')
                            ->nextField('handoff_b')
                            ->placeholder('Scan, focus moves on...'),

                        QrScanner::make('handoff_b')
                            ->label('Second stop')
                            ->placeholder('...lands here'),
                    ]),

                Section::make('7. Scan rules and instant reject')
                    ->description('Submit-time scanRules() plus rejectWhen() for immediate feedback — BAD-prefixed scans clear and notify via qr-scan-rejected.')
                    ->schema([
                        QrScanner::make('guarded_sku')
                            ->label('Guarded SKU')
                            ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
                            ->normalizeUsing(fn ($rawValue) => filled($rawValue) ? strtoupper(trim((string) $rawValue)) : $rawValue)
                            ->scanRules(['min:3'])
                            ->rejectWhen(
                                fn ($state) => str_starts_with((string) $state, 'BAD'),
                                'Reserved prefix — scan rejected.',
                            )
                            ->placeholder('Try BAD-001...'),
                    ]),
            ]);
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Clear All')
                ->color('gray')
                ->action(fn () => $this->form->fill()),
        ];
    }
}
