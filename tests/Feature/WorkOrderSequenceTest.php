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
        ->assertSee('Sequential QR Scanning');
});

it('can submit sequential work order form', function () {
    Livewire::test(WorkOrderSequence::class)
        ->fillForm([
            'batch_number' => 'BATCH-2026-X1',
            'operator_badge' => 'OP-4412',
            'equipment_code' => 'STATION-09',
            'notes' => 'Passed visual inspection calibration.',
        ])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect(WorkOrder::where('batch_number', 'BATCH-2026-X1')->exists())->toBeTrue();
    $order = WorkOrder::where('batch_number', 'BATCH-2026-X1')->first();
    expect($order->operator_badge)->toBe('OP-4412')
        ->and($order->equipment_code)->toBe('STATION-09');
});
