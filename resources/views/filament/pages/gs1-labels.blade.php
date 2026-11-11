<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filament's callout renders its heading as <h4> (out of order under the page's <h1>) and ignores the default slot, so the text goes in the description. --}}
        <x-filament::callout icon="heroicon-o-information-circle" color="info">
            <x-slot name="description">
                <strong class="font-semibold">{{ __('Scan these labels into GS1 Receiving.') }}</strong>
                {{ __('Show this page on one screen and open GS1 Receiving on a phone, or shoot the labels with a handheld scanner set to send FNC1 as GS. Dates are relative to today, so the valid labels never expire.') }}
                <x-filament::link :href="\App\Filament\Pages\Gs1Receiving::getUrl()" size="xs">
                    {{ __('Open GS1 Receiving') }}
                </x-filament::link>
            </x-slot>
        </x-filament::callout>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
