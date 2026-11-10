<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Receive from GS1 logistic labels') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Open the sample labels on another screen or print them, then scan one here. A valid label fills the line; a bad check digit, a missing expiry, expired stock or a plain EAN-13 is rejected with its reason under the field. You can also paste a bracketed label such as (01)08901234567074(17)…') }}
                <x-filament::link :href="\App\Filament\Pages\Gs1Labels::getUrl()" size="xs">
                    {{ __('Open the GS1 sample labels') }}
                </x-filament::link>
            </p>
        </x-filament::callout>

        <form wire:submit="receive" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-inbox-arrow-down">
                {{ __('Receive line') }}
            </x-filament::button>
        </form>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
