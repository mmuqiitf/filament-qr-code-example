<?php

use App\Filament\Pages\QrCustomizer;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render qr customizer studio page', function () {
    Livewire::test(QrCustomizer::class)
        ->assertSuccessful()
        ->assertSee('Interactive QR Customizer');
});

it('generates a valid preview data uri for SVG', function () {
    $component = Livewire::test(QrCustomizer::class)
        ->fillForm([
            'content' => 'https://filamentphp.com/plugins',
            'color' => '#1e293b',
            'backgroundColor' => '#ffffff',
            'format' => 'svg',
            'size' => 240,
        ]);

    $dataUri = $component->get('previewDataUri');
    expect($dataUri)->toBeString()
        ->and($dataUri)->toStartWith('data:image/svg+xml;base64,');
});

it('can download a customized QR code as streamed response', function () {
    Livewire::test(QrCustomizer::class)
        ->fillForm([
            'content' => 'DOWNLOAD-TEST-QR',
            'format' => 'svg',
        ])
        ->call('download')
        ->assertFileDownloaded('custom-qr-code.svg');
});

it('builds common payloads via QrPayload presets without hand-escaping', function () {
    Livewire::test(QrCustomizer::class)
        ->call('applyPayloadPreset', 'wifi')
        ->assertSet('data.content', 'WIFI:T:WPA;S:Shop Floor;P:secret-1;;')
        ->call('applyPayloadPreset', 'geo')
        ->assertSet('data.content', 'geo:-6.2,106.8?q=Warehouse%207');

    $dataUri = Livewire::test(QrCustomizer::class)
        ->call('applyPayloadPreset', 'sms')
        ->get('previewDataUri');

    expect($dataUri)->toBeString()
        ->and($dataUri)->toStartWith('data:image/svg+xml;base64,');
});
