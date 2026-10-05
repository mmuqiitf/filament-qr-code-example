<x-filament-panels::page>
    <div
        class="space-y-6"
        x-on:qr-sequence-step.window="$wire.handleSequenceStep($event.detail.field, $event.detail.value)"
        x-on:qr-sequence-edited.window="$wire.handleSequenceEdited($event.detail.field, $event.detail.value)"
    >
        {{-- Unedited vs edited mode toggle. The Livewire property sets the
             initial mode; the window event flips the running Alpine island
             live (its DOM is Livewire-ignored so scans survive re-renders). --}}
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
            <input
                type="checkbox"
                wire:model.live="allowCorrections"
                @change="window.dispatchEvent(new CustomEvent('qr-sequence-editable', { detail: { enabled: $event.target.checked } }))"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            {{ __('Allow step corrections (unedited vs edited values)') }}
        </label>

        {{-- Instructions Callout --}}
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="amber"
            heading="{{ __('How Sequential Scanning Works') }}"
        >
            <ol class="list-decimal list-inside space-y-1 text-xs mt-1">
                <li>{{ __('Press Start, then point each code at the single shared camera feed — or use your handheld USB/Bluetooth scanner.') }}</li>
                <li>{{ __('Each scan fills the active step, plays audio/haptic feedback, and advances to the next field automatically.') }}</li>
                <li>{{ __('Click any step to re-target it, or pick another camera from the dropdown.') }}</li>
                <li>{{ __('Each step is a plain input: scans fill it, and you can type or correct any value by hand.') }}</li>
                <li>{{ __('Untick corrections above to lock steps read-only.') }}</li>
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
