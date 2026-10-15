<?php

use App\Filament\Pages\CashierCheckout;
use App\Filament\Pages\ScannerShowcase;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('serves every package-backed admin page and its package assets', function (string $uri) {
    Product::factory()->create(['sku' => 'PRD-COMPAT']);

    $html = $this->get($uri)->assertOk()->getContent();

    preg_match_all('#/(?:js|css)/mmuqiitf/filament-qr-code/[^"\'?]+#', $html, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach (array_unique($matches[0]) as $asset) {
        expect(public_path(ltrim($asset, '/')))->toBeFile();
    }
})->with([
    '/admin',
    '/admin/scanner-showcase',
    '/admin/work-order-sequence',
    '/admin/cashier-checkout',
    '/admin/qr-customizer',
    '/admin/products',
    '/admin/products/create',
]);

it('serves product view and edit pages', function () {
    $product = Product::factory()->create(['sku' => 'PRD-VIEW']);

    $this->get("/admin/products/{$product->getKey()}")->assertOk();
    $this->get("/admin/products/{$product->getKey()}/edit")->assertOk();
});

it('ships the P0 interceptor options in the cashier listener payload', function () {
    Livewire::test(CashierCheckout::class)
        ->assertSeeHtml('minFastKeys')
        ->assertSeeHtml('fallbackToAnyInput');

    $payload = QrHardwareScannerListener::make(['scanInput'])
        ->hardwareScanner(terminators: ['Enter', 'Tab'], minBarcodeLength: 2, burstThresholdMs: 50)
        ->getScanPayload();

    expect($payload['minFastKeys'])->toBe(1)
        ->and($payload['fallbackToAnyInput'])->toBeTrue()
        ->and($payload['fields'])->toBe(['scanInput']);
});

it('passes minFastKeys and the fallback switch through when configured', function () {
    $payload = QrHardwareScannerListener::make(['scanInput'])
        ->fallbackToAnyInput(false)
        ->hardwareScanner(minFastKeys: 3)
        ->getScanPayload();

    expect($payload['minFastKeys'])->toBe(3)
        ->and($payload['fallbackToAnyInput'])->toBeFalse();
});

it('renders scanner fields with an explicit state path for the single per-scan write', function () {
    Livewire::test(ScannerShowcase::class)
        ->assertSeeHtml('data-field-name="guarded_sku"')
        ->assertSeeHtml('statePath')
        ->assertSeeHtml('data.guarded_sku');
});

class CompatCollectorPage extends Component implements HasForms
{
    use InteractsWithForms;

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
                Toggle::make('strict'),
                QrCollector::make('codes')
                    ->distinctItems(fn (Get $get): bool => (bool) $get('strict')),
                QrScanner::make('sku')
                    ->live(onBlur: true),
            ]);
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('evaluates distinctItems closures at validation time and honours live(onBlur)', function () {
    Livewire::test(CompatCollectorPage::class)
        ->assertSeeHtml('wire:model.live.blur="data.sku"')
        ->set('data.codes', ['A-1', 'A-1'])
        ->call('submit')
        ->assertHasNoErrors()
        ->set('data.strict', true)
        ->call('submit')
        ->assertHasErrors(['data.codes']);
});

it('publishes the 0.3 camera decoder next to the package script', function () {
    $dir = public_path('js/mmuqiitf/filament-qr-code');

    expect($dir.'/filament-qr-code-decoder.js')->toBeFile()
        ->and(file_get_contents($dir.'/filament-qr-code-scripts.js'))->toContain('./filament-qr-code-decoder.js')
        ->and(is_dir($dir.'/chunks'))->toBeFalse();
});

it('keeps cashier bursts out of unrelated inputs such as search', function () {
    Livewire::test(CashierCheckout::class)
        ->assertSeeHtml('fallbackToAnyInput\\u0022:false');
});
