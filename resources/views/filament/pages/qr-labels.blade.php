<x-filament-panels::page>
    {{-- Print only the label sheet: hide the panel chrome, the form and the callout. --}}
    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .fi-callout, .filament-qr-no-print { display: none !important; }
            .fi-main-ctn, .fi-main, .fi-page { margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .filament-qr-label-sheet { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
            .filament-qr-label { break-inside: avoid; }
        }
    </style>

    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Print the codes the scanning demos expect') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Pick a label set, print the sheet (three labels per row, camera UI hidden) and scan the labels on the POS, Goods Receiving and Manufacturing pages with a phone or a handheld scanner.') }}
            </p>
        </x-filament::callout>

        <div class="filament-qr-no-print space-y-4">
            {{ $this->form }}

            <div class="flex gap-2">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-swatch" wire:click="applyStyle">
                    {{ __('Apply colour') }}
                </x-filament::button>
                <x-filament::button size="sm" icon="heroicon-o-printer" x-on:click="window.print()">
                    {{ __('Print sheet') }}
                </x-filament::button>
            </div>
        </div>

        <div class="filament-qr-label-sheet grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($this->labels() as $label)
                <figure wire:key="label-{{ $label['code'] }}" class="filament-qr-label rounded-xl border border-gray-200 bg-white p-3 text-center dark:border-gray-800">
                    <img src="{{ $this->previewDataUri($label['code']) }}" alt="{{ $label['code'] }}" class="mx-auto" width="{{ $data['size'] ?? 160 }}" height="{{ $data['size'] ?? 160 }}">
                    <figcaption class="mt-2 space-y-0.5">
                        <span class="block font-mono text-sm font-semibold text-gray-900">{{ $label['code'] }}</span>
                        <span class="block text-xs text-gray-500">{{ $label['caption'] }}</span>
                    </figcaption>
                    <button type="button" wire:click="download(@js($label['code']))" class="filament-qr-no-print mt-2 text-xs font-medium text-primary-600 hover:underline">
                        {{ __('Download') }}
                    </button>
                </figure>
            @empty
                <p class="text-sm text-gray-500">{{ __('No codes in this set. Run php artisan db:seed.') }}</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
