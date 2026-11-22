<?php

use App\Filament\Pages\WorkOrderSequence;
use App\Models\User;
use App\Models\WorkOrder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render work order sequential scanning page', function () {
    Livewire::test(WorkOrderSequence::class)
        ->assertSuccessful()
        ->assertSee('Sequential QR Scanning')
        ->assertSee('QR Sequence Scanner')
        ->assertSee('Allow step corrections');
});

it('can submit sequential work order form', function () {
    Livewire::test(WorkOrderSequence::class)
        ->set('data.batch_number', 'BATCH-2026-X1')
        ->set('data.operator_badge', 'OP-4412')
        ->set('data.equipment_code', 'STATION-09')
        ->set('data.notes', 'Passed visual inspection calibration.')
        ->call('submit')
        ->assertHasNoFormErrors();

    expect(WorkOrder::where('batch_number', 'BATCH-2026-X1')->exists())->toBeTrue();
    $order = WorkOrder::where('batch_number', 'BATCH-2026-X1')->first();
    expect($order->operator_badge)->toBe('OP-4412')
        ->and($order->equipment_code)->toBe('STATION-09');
});

it('warns when submitting an incomplete sequence', function () {
    Livewire::test(WorkOrderSequence::class)
        ->set('data.batch_number', 'BATCH-2026-S2')
        ->call('submit')
        ->assertNotified('Incomplete sequence');

    expect(WorkOrder::where('batch_number', 'BATCH-2026-S2')->exists())->toBeFalse();
});

it('notifies when a sequence step is captured', function () {
    Livewire::test(WorkOrderSequence::class)
        ->call('handleSequenceStep', 'batch_number', 'BATCH-777')
        ->assertNotified('Sequence step captured');
});

it('merges sequence component state and normalizes steps on submit', function () {
    Livewire::test(WorkOrderSequence::class)
        ->set('data.sequence', [
            'batch_number' => '  batch-2026-x9  ',
            'operator_badge' => 'op-4412',
            'equipment_code' => 'station-09',
        ])
        ->set('data.notes', 'Normalized via normalizeStepUsing.')
        ->call('submit')
        ->assertHasNoFormErrors();

    $order = WorkOrder::where('batch_number', 'BATCH-2026-X9')->first();
    expect($order)->not->toBeNull()
        ->and($order->operator_badge)->toBe('OP-4412')
        ->and($order->equipment_code)->toBe('STATION-09');
});

it('can lock steps read-only and notifies on corrections', function () {
    Livewire::test(WorkOrderSequence::class)
        ->assertSet('allowCorrections', true)
        ->set('allowCorrections', false)
        ->assertSet('allowCorrections', false)
        ->call('handleSequenceEdited', 'batch_number', 'BATCH-778')
        ->assertNotified('Sequence step corrected');
});

it('clears everything with Clear All, including the scanned steps', function () {
    Livewire::test(WorkOrderSequence::class)
        ->set('data.sequence', ['batch_number' => 'BATCH-42', 'operator_badge' => 'OP-001', 'equipment_code' => 'MC-09'])
        ->set('data.batch_number', 'BATCH-42')
        ->set('data.notes', 'Inspect passed')
        ->call('clearAll')
        ->assertSuccessful()
        ->assertSet('data.batch_number', null)
        ->assertSet('data.notes', null)
        ->assertSet('data.sequence', fn ($sequence): bool => array_filter((array) $sequence) === [])
        ->assertDispatched('qr-sequence-reset');
});

it('does not call the mount lifecycle hook from the page', function () {
    expect(file_get_contents(resource_path('views/filament/pages/work-order-sequence.blade.php')))
        ->not->toContain('wire:click="mount"')
        ->toContain('wire:click="clearAll"');
});

it('resets the scanned steps with the Clear Sequence header action', function () {
    Livewire::test(WorkOrderSequence::class)
        ->set('data.sequence', ['batch_number' => 'BATCH-42', 'operator_badge' => 'OP-001', 'equipment_code' => 'MC-09'])
        ->set('data.equipment_code', 'MC-09')
        ->callAction('reset')
        ->assertSet('data.equipment_code', null)
        ->assertSet('data.sequence', fn ($sequence): bool => array_filter((array) $sequence) === [])
        ->assertDispatched('qr-sequence-reset');
});
