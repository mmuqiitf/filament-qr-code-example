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

it('can lock steps read-only and notifies on corrections', function () {
    Livewire::test(WorkOrderSequence::class)
        ->assertSet('allowCorrections', true)
        ->set('allowCorrections', false)
        ->assertSet('allowCorrections', false)
        ->call('handleSequenceEdited', 'batch_number', 'BATCH-778')
        ->assertNotified('Sequence step corrected');
});
