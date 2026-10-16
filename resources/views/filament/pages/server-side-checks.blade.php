<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Checked on the server') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Try PRD-1001 (accepted), PRD-9999 (unknown SKU), PRD-1007 (out of stock) or abc (fails the format rule). The same checks run again when you confirm.') }}
            </p>
        </x-filament::callout>

        <form wire:submit="submit" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit">
                {{ __('Confirm product') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
