<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Instructions Callout --}}
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="amber"
            heading="{{ __('How Sequential Scanning Works') }}"
        >
            <ol class="list-decimal list-inside space-y-1 text-xs mt-1">
                <li>{{ __('Click the "Scan" camera icon or point your handheld USB/Bluetooth hardware barcode scanner.') }}</li>
                <li>{{ __('Upon successful scan, audio beep/vibration triggers and cursor automatically jumps to the next sequential field.') }}</li>
                <li>{{ __('Station Wedge Listener is active: hardware scanner bursts anywhere on the page route directly into the active field.') }}</li>
            </ol>
        </x-filament::callout>

        {{-- Form --}}
        <form wire:submit.prevent="submit" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center gap-3">
                <x-filament::button type="submit" size="lg">
                    {{ __('Complete & Submit Work Order') }}
                </x-filament::button>

                <x-filament::button type="button" color="gray" wire:click="mount">
                    {{ __('Clear All') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
