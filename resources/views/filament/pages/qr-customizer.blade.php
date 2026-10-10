<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- Left: Configuration Form (7 cols) --}}
        <div class="lg:col-span-7 space-y-6">
            <x-filament::section compact>
                <x-slot name="heading">
                    {{ __('Payload presets (QrPayload)') }}
                </x-slot>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button size="xs" color="gray" wire:click="applyPayloadPreset('wifi')">
                        WiFi
                    </x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="applyPayloadPreset('vcard')">
                        vCard
                    </x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="applyPayloadPreset('mailto')">
                        Mailto
                    </x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="applyPayloadPreset('sms')">
                        SMS
                    </x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="applyPayloadPreset('geo')">
                        Geo
                    </x-filament::button>
                </div>
            </x-filament::section>

            <form wire:submit.prevent="download">
                {{ $this->form }}
            </form>
        </div>

        {{-- Right: Live Dynamic Preview & Download (5 cols) --}}
        <div class="lg:col-span-5 sticky top-6">
            <x-filament::section>
                <x-slot name="heading">
                    <span>{{ __('Live Dynamic Preview') }}</span>
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::badge color="primary">
                        {{ strtoupper($this->data['format'] ?? 'SVG') }}
                    </x-filament::badge>
                </x-slot>

                <div class="space-y-6 text-center">
                    {{-- QR Display Area --}}
                    <div class="flex items-center justify-center p-6 rounded-xl bg-gray-50 dark:bg-white/5 border border-dashed border-gray-200 dark:border-white/10 min-h-[280px]">
                        @if(filled($this->previewDataUri))
                            <img
                                src="{{ $this->previewDataUri }}"
                                alt="Custom QR Code"
                                class="max-h-72 w-auto object-contain transition-all duration-200 rounded-lg shadow-sm"
                                style="max-height: 18rem; width: auto;"
                            />
                        @else
                            <div class="text-xs text-gray-400">
                                {{ __('Unable to generate QR code with current parameters.') }}
                            </div>
                        @endif
                    </div>

                    {{-- Parameters Summary --}}
                    <div class="grid grid-cols-3 gap-2 text-left text-xs bg-gray-50 dark:bg-white/5 p-3 rounded-lg border border-gray-200 dark:border-white/10">
                        <div>
                            <span class="text-gray-400 block">{{ __('Size') }}</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ $this->data['size'] ?? 240 }}px</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">{{ __('EC Level') }}</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">Level {{ $this->data['errorCorrection'] ?? 'M' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">{{ __('Margin') }}</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ $this->data['margin'] ?? 2 }} modules</span>
                        </div>
                    </div>

                    {{-- Download Action --}}
                    <x-filament::button
                        wire:click="download"
                        size="lg"
                        icon="heroicon-o-arrow-down-tray"
                        class="w-full"
                    >
                        {{ __('Download ' . strtoupper($this->data['format'] ?? 'SVG') . ' QR Code') }}
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
