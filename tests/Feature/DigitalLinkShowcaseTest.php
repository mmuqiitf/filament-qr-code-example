<?php

use App\Filament\Pages\DigitalLinkShowcase;
use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('serves the page at /admin/digital-link under the digitalLink route name', function () {
    expect(DigitalLinkShowcase::getUrl())->toEndWith('/admin/digital-link')
        ->and(DigitalLinkShowcase::getRouteName())->toBe('filament.admin.pages.digitalLink');

    $this->get(DigitalLinkShowcase::getUrl())
        ->assertSuccessful()
        ->assertSee('GS1 Digital Link')
        ->assertSee('Build, encode and parse a GS1 Digital Link.')
        ->assertSee('href="'.ProductResource::getUrl('index').'"', escape: false);
});

it('shows the uncompressed URI, a QR of that URI, the GS1 DataMatrix, and the parsed bracketed string', function () {
    $uri = DigitalLinkShowcase::uri();
    $bracketed = DigitalLinkShowcase::bracketed();

    expect($uri)->toBe(DigitalLink::build(DigitalLinkShowcase::elements()))
        ->and($bracketed)->toBe(Gs1::build(DigitalLinkShowcase::elements())->bracketed())
        ->and(DigitalLink::parse($uri)->bracketed())->toBe($bracketed);

    Livewire::test(DigitalLinkShowcase::class)
        ->assertSee($uri)
        ->assertSee($bracketed)
        ->assertSeeHtml('data:image/svg+xml;base64,')
        ->assertDontSeeHtml('data-qr-part="qr.error"')
        ->assertDontSeeHtml('data-qr-part="barcode.error"')
        ->assertSeeHtml('alt="GS1 DataMatrix barcode: '.$bracketed.'"');
});
