<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Receive with a handheld scanner') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Scan PO-7001, then PRD-1001, then a lot such as LOT-A1 (or skip it by scanning the first serial straight away), then one serial per unit, such as SN-PRD-1001-0001. The PO stays for the next item; use Clear to change it.') }}
            </p>
        </x-filament::callout>

        <x-filament::section heading="{{ __('Receiving options') }}" compact>
            <div class="flex flex-wrap items-center gap-6 text-sm">
                <label class="flex items-center gap-2">
                    <x-filament::input.checkbox wire:model.live="autoSubmit" />
                    {{ __('Receive automatically when the item is complete') }}
                </label>
                <label class="flex items-center gap-2">
                    {{ __('Units per item') }}
                    <x-filament::input.wrapper class="w-20">
                        <x-filament::input type="number" min="1" max="20" wire:model.live="quantity" />
                    </x-filament::input.wrapper>
                </label>
            </div>
        </x-filament::section>

        <form wire:submit="submit" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-inbox-arrow-down">
                {{ __('Receive item') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
