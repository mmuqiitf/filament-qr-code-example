<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filament's callout renders its heading as <h4> (out of order under the page's <h1>) and ignores the default slot, so the text goes in the description. --}}
        <x-filament::callout icon="heroicon-o-information-circle" color="info">
            <x-slot name="description">
                <strong class="font-semibold">{{ __('Checked on the server') }}</strong>
                {{ __('Try PRD-1001 (accepted), PRD-9999 (unknown SKU), PRD-1007 (out of stock) or abc (fails the format rule). The same checks run again when you confirm.') }}
            </x-slot>
        </x-filament::callout>

        <form wire:submit="submit" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit">
                {{ __('Confirm product') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
