<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filament's callout renders its heading as <h4> (out of order under the page's <h1>) and ignores the default slot, so the text goes in the description. --}}
        <x-filament::callout icon="heroicon-o-information-circle" color="amber">
            <x-slot name="description">
                <strong class="font-semibold">{{ __('One field, many configurations') }}</strong>
                {{ __('Type or scan into any scenario below. Every field is bound to live form state — open two of them to compare feedback sounds, upload availability, and format filtering side by side.') }}
            </x-slot>
        </x-filament::callout>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
