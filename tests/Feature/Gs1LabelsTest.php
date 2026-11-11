<?php

use App\Filament\Pages\Gs1Labels;
use App\Filament\Pages\Gs1Receiving;
use App\Models\User;
use App\Support\Gs1SampleLabels;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

beforeEach(function () {
    $this->travelTo('2026-11-10 09:00:00');

    $this->actingAs(User::factory()->create());
});

it('serves the page at /admin/gs1-labels under the gs1Labels route name', function () {
    expect(Gs1Labels::getUrl())->toEndWith('/admin/gs1-labels')
        ->and(Gs1Labels::getRouteName())->toBe('filament.admin.pages.gs1Labels');

    $this->get(Gs1Labels::getUrl())->assertSuccessful()->assertSee('GS1 Labels');
});

it('explains the page and links to GS1 Receiving', function () {
    $this->get(Gs1Labels::getUrl())
        ->assertSee('Dates are relative to today, so the valid labels never expire.')
        ->assertSee('href="'.Gs1Receiving::getUrl().'"', escape: false);
});

it('draws every GS1-128 sample with its bracketed text', function () {
    $test = Livewire::test(Gs1Labels::class);

    $drawn = collect(Gs1SampleLabels::all())->where('symbology', 'gs1-128');

    expect($drawn)->not->toBeEmpty();

    foreach ($drawn as $sample) {
        $test->assertSee($sample['value'])
            ->assertSeeHtml('alt="GS1-128 barcode: '.$sample['value'].'"');
    }
});

it('prints the bad check digit sample as Code 128 and shows why GS1-128 refuses it', function () {
    $sample = Gs1SampleLabels::find('bad-check-digit');

    expect($sample['value'])->toBe('(01)08901234567075(17)271110(10)LOT-2611B');

    Livewire::test(Gs1Labels::class)
        ->assertSeeHtml('alt="Code 128 barcode: '.$sample['value'].'"')
        ->assertSeeHtml('data-qr-part="barcode.error"')
        ->assertSee('invalid check digit, expected 4');
});

it('renders one decodable SVG image per sample', function () {
    $html = $this->get(Gs1Labels::getUrl())->assertOk()->getContent();

    preg_match_all('#src="data:image/svg\+xml;base64,([^"]+)"#', $html, $matches);

    expect($matches[1])->toHaveCount(count(Gs1SampleLabels::all()));

    foreach ($matches[1] as $encoded) {
        expect(base64_decode($encoded, true))->toContain('<svg');
    }
});

it('gives each sample the scan outcome its caption promises on the receiving page', function (string $key, ?string $message) {
    $this->seed(DatabaseSeeder::class);

    $test = Livewire::test(Gs1Receiving::class);
    $scannerKey = $test->instance()->form
        ->getComponent(fn ($component): bool => $component instanceof QrScanner && $component->getName() === 'label')
        ->getKey();

    $test->call('callSchemaComponentMethod', $scannerKey, 'handleScan', ['code' => Gs1SampleLabels::find($key)['value'], 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === ($message === null) && $result['message'] === $message);
})->with([
    'label rolls case' => ['label-rolls', null],
    'serialised battery' => ['battery-pack', null],
    'weighed wipes pallet' => ['wipes-pallet', null],
    'bad check digit' => ['bad-check-digit', 'Invalid check digit in AI (01)'],
    'missing expiry' => ['missing-expiry', 'Missing AI (17) USE BY or EXPIRY'],
    'expired' => ['expired', 'Expired on 2026-10-11. Quarantine the stock instead of receiving it.'],
    'plain EAN-13' => ['plain-ean', 'Plain product barcode, not a GS1 label'],
]);

it('keeps the valid samples valid as time passes', function () {
    $this->seed(DatabaseSeeder::class);

    $this->travelTo(now()->addYears(3));

    $test = Livewire::test(Gs1Receiving::class);
    $key = $test->instance()->form
        ->getComponent(fn ($component): bool => $component instanceof QrScanner && $component->getName() === 'label')
        ->getKey();

    foreach (['label-rolls', 'battery-pack', 'wipes-pallet'] as $sample) {
        $test->call('callSchemaComponentMethod', $key, 'handleScan', ['code' => Gs1SampleLabels::find($sample)['value'], 'source' => 'camera'])
            ->assertReturned(fn (array $result): bool => $result['passes'] === true);
    }
});
