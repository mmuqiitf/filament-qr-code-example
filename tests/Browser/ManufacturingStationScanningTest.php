<?php

use App\Models\AssemblyBuild;
use App\Models\AssemblyOrder;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    AssemblyOrder::factory()->create(['number' => 'WO-5001', 'product_name' => 'Scanner kit'])
        ->bomLines()->createMany([
            ['component_sku' => 'CMP-100', 'quantity' => 2],
            ['component_sku' => 'CMP-200', 'quantity' => 1],
        ]);
});

// signIn() lives in QrUploadScanningTest.php; wedgeBurst() in GoodsReceivingScanningTest.php.

it('renders the station with its styling hooks and no JavaScript errors', function () {
    signIn()
        ->navigate('/admin/manufacturing')
        ->assertSee('Manufacturing Station')
        ->assertSee('Operator badge')
        ->assertPresent('.fi-qr-sequence.mfg-station')
        ->assertPresent('[data-mfg-legend]')
        ->assertNoJavaScriptErrors();
});

it('builds a unit from handheld bursts, rejecting a part that is not on the BOM', function () {
    $page = signIn()->navigate('/admin/manufacturing')->waitForEvent('networkidle');

    foreach (['op-001', 'WO-5001', 'CMP-100:0001', 'CMP-999:0001'] as $code) {
        wedgeBurst($page, $code);
    }

    $page->assertSee('CMP-999 is not on the BOM for WO-5001.')->assertSee('Siti Rahma signed in');

    foreach (['CMP-200:0001', 'CMP-100:0002', 'DONE'] as $code) {
        wedgeBurst($page, $code);
    }

    $page->wait(1)->assertSee('Built one Scanner kit')->assertNoJavaScriptErrors();

    expect(AssemblyBuild::sole()->components)->toBe(['CMP-100:0001', 'CMP-200:0001', 'CMP-100:0002']);
});

it('pauses and resumes scanning from the page buttons', function () {
    $page = signIn()->navigate('/admin/manufacturing')->waitForEvent('networkidle');

    $page->click('Pause scanning');
    wedgeBurst($page, 'OP-001');
    expect(AssemblyBuild::count())->toBe(0);
    $page->assertDontSee('Siti Rahma signed in');

    $page->click('Resume');
    wedgeBurst($page, 'OP-001');
    $page->assertSee('Siti Rahma signed in')->assertNoJavaScriptErrors();
});
