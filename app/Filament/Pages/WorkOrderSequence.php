<?php

namespace App\Filament\Pages;

use App\Models\WorkOrder;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

/**
 * Sequential scanning: ONE shared camera feed drives MULTIPLE fields.
 *
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

    public bool $allowCorrections = true;

    public function mount(): void
    {
        $this->form->fill([
            'status' => 'in_progress',
        ]);
    }

    public function handleSequenceStep(string $field, string $value): void
    {
        Notification::make()
            ->title('Sequence step captured')
            ->body("{$field}: {$value}")
            ->success()
            ->duration(2000)
            ->send();
    }

    public function handleSequenceEdited(string $field, string $value): void
    {
        Notification::make()
            ->title('Sequence step corrected')
            ->body("{$field}: {$value}")
            ->success()
            ->duration(2000)
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Work Order Sequence')
                    ->description('One shared camera feed walks through every field below. Hardware bursts feed the active step directly.')
                    ->schema([
                        QrScanSequence::make([
                            'batch_number' => '1. Batch / Job Ticket',
                            'operator_badge' => '2. Operator ID Badge',
                            'equipment_code' => '3. Machine / Equipment',
                        ])
                            ->fps(25)
                            ->qrbox(250)
                            ->formats([
                                BarcodeFormat::QrCode,
                                BarcodeFormat::Code128,
                                BarcodeFormat::Code39,
                                BarcodeFormat::Ean13,
                            ])
                            ->preferRearCamera()
                            ->statePathPrefix('data')
                            ->editable(fn (): bool => $this->allowCorrections)
                            ->sound(true)
                            ->vibrate(true)
                            ->hardwareScanner(enabled: true, burstThresholdMs: 50),

                        Textarea::make('notes')
                            ->label('4. Operational Notes / Inspection Remarks')
                            ->rows(3)
                            ->placeholder('Optional comments or inspection status...'),
                    ]),
            ]);
    }

    public function submit(): void
    {
        // Merge raw form data first: the sequence container writes scans
        // straight into Livewire state via $wire.set, bypassing field
        // components, so getState() alone would miss them.
        $state = array_merge($this->data ?? [], $this->form->getState());

        $missing = collect(['batch_number', 'operator_badge', 'equipment_code'])
            ->filter(fn (string $key): bool => blank($state[$key] ?? null));

        if ($missing->isNotEmpty()) {
            Notification::make()
                ->title('Incomplete sequence')
                ->body('Scan the batch, operator, and equipment codes before submitting.')
                ->warning()
                ->send();

            return;
        }

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
