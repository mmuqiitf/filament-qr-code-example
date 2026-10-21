<?php

namespace App\Filament\Pages;

use App\Models\AssemblyBuild;
use App\Models\AssemblyOrder;
use App\Scanning\ComponentBelongsToBom;
use App\Support\BadgeDirectory;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LogicException;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

/**
 * A work-order station built only on the package's public API:
 *
 * - operator badge from a non-Eloquent directory (lookupUsing), sticky
 * - open work order (lookup with a query), sticky
 * - rework reason, asked only on rework orders (when)
 * - components, open-ended (repeat + finishWith DONE), each checked by a
 *   piped stage (ComponentBelongsToBom); duplicate serials are rejected
 * - the whole BOM checked when the operator finishes (validateAllUsing)
 * - an activity feed from onStepAccepted / onStepRejected (side effects only)
 * - runtime-control buttons (resetSequence, goToStep, qr-sequence-pause)
 * - styling: extraAttributes, CSS variables, a render hook, a published sub-view
 *
 * @property Schema $form
 */
class ManufacturingStation extends Page
{
    protected string $view = 'filament.pages.manufacturing-station';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Manufacturing Station';

    protected static ?string $navigationLabel = 'Manufacturing';

    protected static ?string $slug = 'manufacturing';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Newest first.
     *
     * @var list<array{step: string, text: string, rejected: bool}>
     */
    public array $activity = [];

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'manufacturing';
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
                QrScanSequence::make()
                    ->statePath('station')
                    ->steps([
                        ScanStep::make('operator')
                            ->label('Operator badge')
                            ->validateUsing(['regex:/^OP-\d{3}$/'])
                            ->lookupUsing(fn (string $code): ?array => app(BadgeDirectory::class)->find($code))
                            ->sticky(),
                        ScanStep::make('order')
                            ->label('Work order')
                            ->lookup(AssemblyOrder::class, 'number', fn ($query) => $query->where('status', 'open'))
                            ->sticky(),
                        ScanStep::make('rework_reason')
                            ->label('Rework reason')
                            ->when(fn (array $values): bool => (bool) AssemblyOrder::findOpen($values['order'] ?? null)?->is_rework)
                            ->validateUsing(['regex:/^RW-\d{2}$/']),
                        ScanStep::make('components')
                            ->label('Components')
                            ->validateUsing(['regex:/^CMP-\d{3}:\d{4}$/'])
                            // Open-ended: the operator scans DONE (or presses Done) when the unit is
                            // assembled, and validateAllUsing() checks the BOM is complete.
                            ->repeat()
                            ->finishWith('DONE')
                            ->pipe(ComponentBelongsToBom::class),
                    ])
                    ->normalizeWith(uppercase: true)
                    ->validateAllUsing(fn (array $values): array => ['components' => $this->missingComponents($values)])
                    ->onStepAccepted(fn (string $step, ScanResult $result) => $this->logActivity($step, $result))
                    ->onStepRejected(fn (string $step, ScanResult $result) => $this->logActivity($step, $result))
                    ->autoSubmit()
                    ->resetAfterSubmit()
                    ->editable(false)
                    ->extraAttributes(['class' => 'mfg-station'])
                    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128]),
            ]);
    }

    public function submit(): void
    {
        // getState() re-runs every step check and validateAllUsing().
        $station = $this->form->getState()['station'] ?? [];
        $order = AssemblyOrder::findOpen($station['order'] ?? null);
        $operator = app(BadgeDirectory::class)->find((string) ($station['operator'] ?? ''));

        if ($order === null || $operator === null) {
            Notification::make()->title('Scan your badge and a work order first.')->warning()->send();

            return;
        }

        AssemblyBuild::create([
            'assembly_order_id' => $order->getKey(),
            'operator_badge' => $operator['badge'],
            'operator_name' => $operator['name'],
            'rework_reason' => $station['rework_reason'] ?? null,
            'components' => array_values($station['components'] ?? []),
        ]);

        Notification::make()
            ->title("Built one {$order->product_name}")
            ->body("{$order->number}: {$order->builds()->count()} unit(s) so far.")
            ->success()
            ->send();

        $this->sequence()->resetSequence();
    }

    public function restartUnit(): void
    {
        $this->sequence()->resetSequence();
    }

    public function changeOperator(): void
    {
        $this->sequence()->resetSequence(keepSticky: false);
    }

    public function backToComponents(): void
    {
        $this->sequence()->goToStep('components');
    }

    /**
     * Message for validateAllUsing(): empty when every BOM unit is fitted.
     *
     * @param  array<string, mixed>  $values
     */
    public function missingComponents(array $values): ?string
    {
        $order = AssemblyOrder::findOpen($values['order'] ?? null);

        if ($order === null) {
            return null;
        }

        $fitted = array_count_values(array_map(
            fn (mixed $code): string => ComponentBelongsToBom::sku((string) $code),
            (array) ($values['components'] ?? []),
        ));

        $missing = [];

        foreach ($order->bom() as $sku => $quantity) {
            if (($short = $quantity - ($fitted[$sku] ?? 0)) > 0) {
                $missing[] = "{$short} × {$sku}";
            }
        }

        return $missing === [] ? null : 'BOM incomplete: missing '.implode(', ', $missing).'.';
    }

    public function logActivity(string $step, ScanResult $result): void
    {
        $text = match (true) {
            ! $result->passes => "{$result->code}: {$result->message}",
            $step === 'operator' && is_array($result->record) => "{$result->record['name']} signed in",
            default => "{$step}: {$result->code}",
        };

        array_unshift($this->activity, ['step' => $step, 'text' => $text, 'rejected' => ! $result->passes]);
        $this->activity = array_slice($this->activity, 0, 8);
    }

    protected function sequence(): QrScanSequence
    {
        $sequence = $this->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);

        if (! $sequence instanceof QrScanSequence) {
            throw new LogicException('The form has no QrScanSequence.');
        }

        return $sequence;
    }
}
