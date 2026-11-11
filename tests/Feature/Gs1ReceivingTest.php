<?php

use App\Filament\Pages\Gs1Receiving;
use App\Models\Product;
use App\Models\ReceivedLine;
use App\Models\User;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

beforeEach(function () {
    $this->travelTo('2026-11-10 09:00:00');

    $this->actingAs(User::factory()->create());

    $this->labelRolls = Product::factory()->create([
        'sku' => 'PRD-1008',
        'barcode' => '8901234567074',
        'name' => 'Thermal Label Rolls',
    ]);
});

function gs1LabelScannerKey(mixed $test): string
{
    return $test->instance()->form
        ->getComponent(fn ($component): bool => $component instanceof QrScanner && $component->getName() === 'label')
        ->getKey();
}

function scanGs1Label(mixed $test, string $code, string $source = 'hardware'): mixed
{
    return $test->call('callSchemaComponentMethod', gs1LabelScannerKey($test), 'handleScan', ['code' => $code, 'source' => $source]);
}

it('serves the page at /admin/gs1-receiving under the gs1Receiving route name', function () {
    expect(Gs1Receiving::getUrl())->toEndWith('/admin/gs1-receiving')
        ->and(Gs1Receiving::getRouteName())->toBe('filament.admin.pages.gs1Receiving');

    $this->get(Gs1Receiving::getUrl())->assertSuccessful()->assertSee('GS1 Receiving');
});

it('fills the line from a raw gun scan and stores the bracketed label', function () {
    $raw = ']C1'.'0108901234567074'.'17271100'.'10LOT-2611A'.Gs1::SEPARATOR.'3103012500'.'3012';

    scanGs1Label(Livewire::test(Gs1Receiving::class), $raw)
        ->assertReturned(fn (array $result): bool => $result['passes'] === true)
        ->assertSet('data.label', '(01)08901234567074(17)271100(10)LOT-2611A(3103)012500(30)12')
        ->assertSet('data.gtin', '08901234567074')
        ->assertSet('data.expires_at', '2027-11-30')
        ->assertSet('data.batch', 'LOT-2611A')
        ->assertSet('data.net_weight_kg', '12.500')
        ->assertSet('data.quantity', '12')
        ->assertSet('data.product', 'Thermal Label Rolls')
        ->assertHasNoErrors(['data.label']);
});

it('fills the serial and keeps the default count when the label has no AI (30)', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '(01)08901234567074(17)271110(21)SN-0042')
        ->assertSet('data.serial', 'SN-0042')
        ->assertSet('data.expires_at', '2027-11-10')
        ->assertSet('data.batch', null)
        ->assertSet('data.quantity', 1);
});

it('clears the fields an earlier label filled when the next label does not carry them', function () {
    $test = Livewire::test(Gs1Receiving::class);

    scanGs1Label($test, '(01)08901234567074(17)271100(10)LOT-2611A(3103)012500(30)12');
    scanGs1Label($test, '(01)08901234567074(17)271110(21)SN-0042')
        ->assertSet('data.serial', 'SN-0042')
        ->assertSet('data.batch', null)
        ->assertSet('data.net_weight_kg', null)
        ->assertSet('data.quantity', 1);
});

it('fills the line from a pasted bracketed label', function () {
    Livewire::test(Gs1Receiving::class)
        ->set('data.label', '(01)08901234567074(17)271110(10)LOT-2611A')
        ->assertSet('data.gtin', '08901234567074')
        ->assertSet('data.batch', 'LOT-2611A')
        ->assertSet('data.product', 'Thermal Label Rolls');
});

it('clears the line when a pasted label is not valid GS1', function () {
    Livewire::test(Gs1Receiving::class)
        ->set('data.label', '(01)08901234567074(17)271110(10)LOT-2611A')
        ->set('data.label', '8901234567074')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', null)
        ->assertSet('data.product', null);
});

it('shows a typed label\'s rejection under the field, once', function () {
    $test = Livewire::test(Gs1Receiving::class)
        ->set('data.label', '8901234567074')
        ->set('data.label', '(01)08901234567075(17)271110')
        ->assertSet('data.label', null)
        ->assertHasErrors(['data.label'])
        ->assertSee('Invalid check digit in AI (01)')
        ->assertDontSee('Plain product barcode, not a GS1 label');

    expect($test->errors()->get('data.label'))->toBe(['Invalid check digit in AI (01)']);
});

it('runs the page checks on a typed label too', function () {
    Livewire::test(Gs1Receiving::class)
        ->set('data.label', '(01)08901234567074(17)261109(10)LOT-OLD')
        ->assertSet('data.gtin', null)
        ->assertSee('Expired on 2026-11-09. Quarantine the stock instead of receiving it.');
});

it('empties fields an earlier pasted label filled when the next one does not carry them', function () {
    Livewire::test(Gs1Receiving::class)
        ->set('data.label', '(01)08901234567074(17)271100(10)LOT-2611A(3103)012500(30)12')
        ->assertSet('data.quantity', '12')
        ->set('data.label', '(01)08901234567074(17)271110(21)SN-0042')
        ->assertSet('data.serial', 'SN-0042')
        ->assertSet('data.batch', null)
        ->assertSet('data.net_weight_kg', null)
        ->assertSet('data.quantity', 1);
});

it('clears the line when a scan is rejected after an accepted one', function () {
    $test = Livewire::test(Gs1Receiving::class);

    scanGs1Label($test, '(01)08901234567074(17)271100(10)LOT-2611A(30)12')->assertSet('data.batch', 'LOT-2611A');

    scanGs1Label($test, '8901234567074')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', null)
        ->assertSet('data.batch', null)
        ->assertSet('data.quantity', 1)
        ->assertSet('data.product', null)
        ->assertHasErrors(['data.label']);
});

it('rejects a label without an expiry date and fills nothing', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '(01)08901234567074(10)LOT-2611A')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'Missing AI (17) USE BY or EXPIRY')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', null)
        ->assertSet('data.batch', null)
        ->assertHasErrors(['data.label'])
        ->assertSee('Missing AI (17) USE BY or EXPIRY');
});

it('rejects a label with a bad GTIN check digit', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '(01)08901234567075(17)271110(10)LOT-2611A')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'Invalid check digit in AI (01)')
        ->assertSet('data.gtin', null)
        ->assertSee('Invalid check digit in AI (01)');
});

it('rejects a plain EAN-13 product barcode', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '8901234567074')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'Plain product barcode, not a GS1 label')
        ->assertSet('data.gtin', null)
        ->assertSee('Plain product barcode, not a GS1 label');
});

it('rejects an expired label', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '(01)08901234567074(17)261109(10)LOT-OLD')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'Expired on 2026-11-09. Quarantine the stock instead of receiving it.')
        ->assertSet('data.gtin', null);
});

it('accepts a label that expires today', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '(01)08901234567074(17)261110(10)LOT-LAST')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true);
});

it('rejects a GTIN that is not in the product catalogue', function () {
    scanGs1Label(Livewire::test(Gs1Receiving::class), '(01)09506000134352(17)271110(10)ABC')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'GTIN 09506000134352 is not in the product catalogue.');
});

it('clears an earlier rejection on the next accepted scan', function () {
    $test = Livewire::test(Gs1Receiving::class);

    scanGs1Label($test, '8901234567074')->assertHasErrors(['data.label']);

    scanGs1Label($test, '(01)08901234567074(17)271110(10)LOT-2611A')
        ->assertHasNoErrors(['data.label'])
        ->assertDontSee('Plain product barcode, not a GS1 label');
});

it('saves the scanned line linked to the product by GTIN', function () {
    $test = Livewire::test(Gs1Receiving::class);

    scanGs1Label($test, '(01)08901234567074(17)271100(10)LOT-2611A(3103)012500(30)12');

    $test->call('receive')
        ->assertHasNoFormErrors()
        ->assertNotified('Received 12 × Thermal Label Rolls')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', null);

    $line = ReceivedLine::sole();

    expect($line->product->is($this->labelRolls))->toBeTrue()
        ->and($line->label)->toBe('(01)08901234567074(17)271100(10)LOT-2611A(3103)012500(30)12')
        ->and($line->gtin)->toBe('08901234567074')
        ->and($line->batch)->toBe('LOT-2611A')
        ->and($line->serial)->toBeNull()
        ->and($line->expires_at->toDateString())->toBe('2027-11-30')
        ->and($line->net_weight_kg)->toBe('12.500')
        ->and($line->quantity)->toBe(12);
});

it('refuses to save a typed label that fails the GS1 check', function () {
    Livewire::test(Gs1Receiving::class)
        ->fillForm([
            'label' => '(01)08901234567075(17)271110',
            'gtin' => '08901234567075',
            'expires_at' => '2027-11-10',
            'quantity' => 1,
        ])
        ->call('receive')
        ->assertHasFormErrors(['label']);

    expect(ReceivedLine::count())->toBe(0);
});

it('lists received lines with their label drawn as GS1-128 through the signed image route', function () {
    ReceivedLine::factory()->for($this->labelRolls)->create([
        'label' => '(01)08901234567074(17)271110(10)LOT-2611A',
        'gtin' => '08901234567074',
        'batch' => 'LOT-2611A',
    ]);

    $html = Livewire::test(Gs1Receiving::class)
        ->assertSee('LOT-2611A')
        ->assertSeeHtml('alt="GS1-128 barcode: (01)08901234567074(17)271110(10)LOT-2611A"')
        ->html();

    preg_match('#src="([^"]*filament-qr-code/barcode-image[^"]*)"#', $html, $match);

    expect($match)->not->toBeEmpty();

    $this->get(html_entity_decode($match[1]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});
