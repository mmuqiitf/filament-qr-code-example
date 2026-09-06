<?php

namespace App\Filament\Pages;

use App\Models\WorkOrder;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrWedgeListener;

/**
 * @property Schema $form
 */
class WorkOrderSequence extends Page
{
    protected string $view = 'filament.pages.work-order-sequence';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Sequential QR Scanning';

    protected static ?string $navigationLabel = 'Sequential Scanning';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $activeMode = 'chained';

    public function mount(): void
    {
        $this->form->fill([
            'status' => 'in_progress',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrWedgeListener::make([
                    'batch_number',
                    'operator_badge',
                    'equipment_code',
                ])
                    ->autoFocusNext(true)
                    ->sound(true),

                Section::make('Chained Sequential Fields')
                    ->description('Each scan automatically validates, plays sensory feedback, and shifts focus directly to the next field in sequence.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                QrScanner::make('batch_number')
                                    ->label('1. Batch / Job Ticket')
                                    ->placeholder('Scan batch barcode...')
                                    ->nextField('operator_badge')
                                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
                                    ->sound(true)
                                    ->vibrate(true)
                                    ->hardwareScanner(enabled: true, burstThresholdMs: 50)
                                    ->required(),

                                QrScanner::make('operator_badge')
                                    ->label('2. Operator ID Badge')
                                    ->placeholder('Scan employee badge...')
                                    ->nextField('equipment_code')
                                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code39])
                                    ->sound(true)
                                    ->vibrate(true)
                                    ->hardwareScanner(enabled: true, burstThresholdMs: 50)
                                    ->required(),

                                QrScanner::make('equipment_code')
                                    ->label('3. Machine / Equipment')
                                    ->placeholder('Scan machine QR...')
                                    ->nextField('notes')
                                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Ean13])
                                    ->sound(true)
                                    ->vibrate(true)
                                    ->hardwareScanner(enabled: true, burstThresholdMs: 50)
                                    ->required(),
                            ]),

                        Textarea::make('notes')
                            ->label('4. Operational Notes / Inspection Remarks')
                            ->rows(3)
                            ->placeholder('Optional comments or inspection status...'),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        WorkOrder::create([
            'batch_number' => $state['batch_number'],
            'operator_badge' => $state['operator_badge'],
            'equipment_code' => $state['equipment_code'],
            'status' => 'in_progress',
            'notes' => $state['notes'] ?? null,
        ]);

        Notification::make()
            ->title('Work Order Logged Successfully')
            ->body("Batch {$state['batch_number']} recorded for operator {$state['operator_badge']}.")
            ->success()
            ->send();

        $this->form->fill([
            'batch_number' => '',
            'operator_badge' => '',
            'equipment_code' => '',
            'notes' => '',
            'status' => 'in_progress',
        ]);
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Clear Sequence')
                ->color('gray')
                ->action(fn () => $this->form->fill()),
        ];
    }
}
