<?php

namespace App\Filament\Pages;

use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

/**
 * Server-side scan validation: rules, a Product lookup, a stock check and
 * scan callbacks that fill the rest of the form.
 *
 * @property Schema $form
 */
class ServerSideChecks extends Page
{
    protected string $view = 'filament.pages.server-side-checks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'Workflows & Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Server-side Checks';

    protected static ?string $navigationLabel = 'Server-side Checks';

    protected static ?string $slug = 'server-side-checks';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Server-side checks')
                    ->description('Each scan is checked on the server: SKU format rules, a Product lookup by SKU, then a stock check. Accepted scans fill the name and price; rejected scans clear the field. Repeats of the same code within 2 seconds are ignored.')
                    ->schema([
                        QrScanner::make('sku')
                            ->label('Product SKU')
                            ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
                            ->validateUsing(['required', 'string', 'regex:/^PRD-\d{4}$/'])
                            ->lookup(Product::class, 'sku')
                            ->validateUsing(fn (?Product $record): ?string => $record?->stock > 0
                                ? null
                                : "{$record?->name} is out of stock.")
                            // $record receives the lookup result because of its name.
                            ->onScanned(function (?Product $record, Set $set): void {
                                $set('name', $record?->name);
                                $set('price', $record?->price);
                            })
                            ->onRejected(function (ScanResult $result, Set $set): void {
                                $set('name', null);
                                $set('price', null);

                                Notification::make()
                                    ->title(__('Scan rejected'))
                                    ->body($result->message)
                                    ->danger()
                                    ->send();
                            })
                            ->clearOnReject()
                            ->cooldown(2000)
                            ->beep(success: true, error: true)
                            ->vibrate(success: [100], error: [80, 60, 80])
                            ->flash()
                            ->placeholder('Scan PRD-1001...'),

                        TextInput::make('name')
                            ->label('Product name')
                            ->readOnly(),

                        TextInput::make('price')
                            ->label('Price')
                            ->prefix('$')
                            ->readOnly(),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        Notification::make()
            ->title(__('Product :sku confirmed', ['sku' => $data['sku']]))
            ->success()
            ->send();
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Clear')
                ->color('gray')
                ->action(fn () => $this->form->fill()),
        ];
    }
}
