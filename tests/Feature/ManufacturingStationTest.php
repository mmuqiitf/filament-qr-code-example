<?php

use App\Filament\Pages\ManufacturingStation;
use App\Models\AssemblyBuild;
use App\Models\AssemblyOrder;
use App\Models\User;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // WO-5001: 2 × CMP-100 and 1 × CMP-200. WO-5002 is a rework order. WO-5003 is closed.
    $order = AssemblyOrder::factory()->create(['number' => 'WO-5001', 'product_name' => 'Scanner kit']);
    $order->bomLines()->createMany([
        ['component_sku' => 'CMP-100', 'quantity' => 2],
        ['component_sku' => 'CMP-200', 'quantity' => 1],
    ]);

    AssemblyOrder::factory()->create(['number' => 'WO-5002', 'is_rework' => true])
        ->bomLines()->create(['component_sku' => 'CMP-300', 'quantity' => 1]);

    AssemblyOrder::factory()->create(['number' => 'WO-5003', 'status' => 'done']);
});

function station(mixed $test): QrScanSequence
{
    return $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);
}

function stationScan(mixed $test, string $step, string $code): array
{
    $test->call('callSchemaComponentMethod', station($test)->getKey(), 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => 'hardware']);

    return $test->effects['returns'][0];
}

function startUnit(mixed $test, string $order = 'WO-5001'): void
{
    stationScan($test, 'operator', 'OP-001');
    stationScan($test, 'order', $order);
}

it('serves the page at /admin/manufacturing under the manufacturing route name', function () {
    expect(ManufacturingStation::getUrl())->toEndWith('/admin/manufacturing')
        ->and(ManufacturingStation::getRouteName())->toBe('filament.admin.pages.manufacturing');

    $this->get(ManufacturingStation::getUrl())->assertSuccessful()->assertSee('Manufacturing Station');
});

it('finds operators in the badge directory, not the database', function () {
    $test = Livewire::test(ManufacturingStation::class);

    expect(stationScan($test, 'operator', 'op-001'))->toMatchArray(['passes' => true, 'code' => 'OP-001', 'next_step' => 'order'])
        ->and($test->get('activity')[0])->toMatchArray(['step' => 'operator', 'text' => 'Siti Rahma signed in']);

    $test = Livewire::test(ManufacturingStation::class);

    expect(stationScan($test, 'operator', 'OP-404'))->toMatchArray(['passes' => false, 'next_step' => 'operator'])
        // OP-009 is in the directory but inactive.
        ->and(stationScan($test, 'operator', 'OP-009'))->toMatchArray(['passes' => false]);
});

it('only accepts open work orders', function () {
    $test = Livewire::test(ManufacturingStation::class);
    stationScan($test, 'operator', 'OP-001');

    expect(stationScan($test, 'order', 'WO-5003'))->toMatchArray(['passes' => false, 'next_step' => 'order'])
        ->and(stationScan($test, 'order', 'WO-5001'))->toMatchArray(['passes' => true, 'next_step' => 'components']);
});

it('asks for a rework reason only on rework orders', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test, 'WO-5002');

    expect(station($test)->getScanPayload()['steps'][2])->toMatchArray(['key' => 'rework_reason', 'active' => true])
        ->and(stationScan($test, 'rework_reason', 'RW-07'))->toMatchArray(['passes' => true, 'next_step' => 'components']);

    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);

    expect(station($test)->getScanPayload()['steps'][2])->toMatchArray(['key' => 'rework_reason', 'active' => false]);
});

it('collects the components until DONE and builds the unit', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);

    stationScan($test, 'components', 'CMP-100:0001');
    stationScan($test, 'components', 'CMP-200:0001');
    stationScan($test, 'components', 'CMP-100:0002');

    expect(stationScan($test, 'components', 'DONE'))->toMatchArray([
        'completed' => true,
        'submitted' => true,
        'reset' => true,
        // Operator and order are sticky: the next unit starts at components.
        'values' => ['operator' => 'OP-001', 'order' => 'WO-5001'],
        'next_step' => 'components',
    ]);

    expect(AssemblyBuild::sole())
        ->operator_badge->toBe('OP-001')
        ->components->toBe(['CMP-100:0001', 'CMP-200:0001', 'CMP-100:0002']);
});

it('rejects a component that is not on the BOM, or one too many, through the piped stage', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);

    expect(stationScan($test, 'components', 'CMP-999:0001'))->toMatchArray(['passes' => false, 'message' => 'CMP-999 is not on the BOM for WO-5001.']);

    stationScan($test, 'components', 'CMP-200:0001');

    expect(stationScan($test, 'components', 'CMP-200:0002'))->toMatchArray(['passes' => false, 'message' => 'All 1 × CMP-200 are already fitted.'])
        ->and($test->get('activity')[0])->toMatchArray(['step' => 'components', 'rejected' => true]);
});

it('rejects a duplicate serial', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);

    stationScan($test, 'components', 'CMP-100:0001');

    expect(stationScan($test, 'components', 'CMP-100:0001'))->toMatchArray(['passes' => false, 'next_step' => 'components']);
});

it('fails validateAllUsing when the operator finishes an incomplete BOM', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);
    stationScan($test, 'components', 'CMP-100:0001');

    expect(stationScan($test, 'components', 'DONE'))->toMatchArray([
        'passes' => false,
        'invalid_step' => 'components',
        'next_step' => 'components',
        'message' => 'BOM incomplete: missing 1 × CMP-100, 1 × CMP-200.',
    ]);

    expect(AssemblyBuild::count())->toBe(0);
});

it('fails validateAllUsing when the BOM is incomplete on submit', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);
    stationScan($test, 'components', 'CMP-100:0001');

    $test->call('submit')->assertHasErrors(['data.station.components']);

    expect(AssemblyBuild::count())->toBe(0);
});

it('drives the sequence from the page: back to components, restart and change operator', function () {
    $test = Livewire::test(ManufacturingStation::class);
    startUnit($test);
    stationScan($test, 'components', 'CMP-100:0001');

    $test->call('restartUnit')
        ->assertSet('data.station', ['operator' => 'OP-001', 'order' => 'WO-5001'])
        ->assertDispatched('qr-sequence-reset', field: 'form.station', values: ['operator' => 'OP-001', 'order' => 'WO-5001'], fromServer: true);

    $test->call('backToComponents')->assertDispatched('qr-sequence-go-to', field: 'form.station', step: 'components');

    $test->call('changeOperator')->assertDispatched('qr-sequence-reset', field: 'form.station', values: [], fromServer: true);
    expect(array_filter($test->get('data.station') ?? []))->toBe([]);
});

it('styles the station with extra attributes, CSS variables, a render hook and the published step chip', function () {
    Livewire::test(ManufacturingStation::class)
        ->assertSeeHtml('mfg-station')
        ->assertSeeHtml('data-mfg-legend')
        ->assertSeeHtml('data-mfg-sticky-pin')
        ->assertSeeHtml("\$dispatch('qr-sequence-pause', { field: 'data.station' })");
});
