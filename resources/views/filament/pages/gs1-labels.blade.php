<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Scan these labels into GS1 Receiving') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Show this page on one screen and open GS1 Receiving on a phone, or shoot the labels with a handheld scanner set to send FNC1 as GS. Dates are relative to today, so the valid labels never expire.') }}
                <x-filament::link :href="\App\Filament\Pages\Gs1Receiving::getUrl()" size="xs">
                    {{ __('Open GS1 Receiving') }}
                </x-filament::link>
            </p>
        </x-filament::callout>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
