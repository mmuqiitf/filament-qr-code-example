<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="amber"
            heading="{{ __('One field, many configurations') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Type or scan into any scenario below. Every field is bound to live form state — open two of them to compare feedback sounds, upload availability, and format filtering side by side.') }}
            </p>
        </x-filament::callout>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
