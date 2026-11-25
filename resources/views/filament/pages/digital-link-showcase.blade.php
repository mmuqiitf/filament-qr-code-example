<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout icon="heroicon-o-information-circle" color="info">
            <x-slot name="description">
                <strong class="font-semibold">{{ __('Build, encode and parse a GS1 Digital Link.') }}</strong>
                {{ __('The QR carries an uncompressed Digital Link URI for the sample GTIN and lot. Scan it into GS1 Receiving or a QrScanner::gs1() field; the field stores the bracketed string. The Products table print action uses the same pairing: a Digital Link QR next to a GS1 DataMatrix.') }}
                <x-filament::link :href="\App\Filament\Resources\Products\ProductResource::getUrl('index')" size="xs">
                    {{ __('Open Products to print labels') }}
                </x-filament::link>
            </x-slot>
        </x-filament::callout>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
