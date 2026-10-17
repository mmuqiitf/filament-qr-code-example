<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Scan, scan, scan') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Shoot barcodes with a handheld scanner anywhere on this page (no need to click the field), or use the camera. Try PRD-1001 twice (quantity 2) and PRD-9999 (unknown SKU).') }}
            </p>
        </x-filament::callout>

        <x-filament::section heading="{{ __('Scanner options') }}" compact>
            <div class="flex flex-wrap gap-6 text-sm">
                <label class="flex items-center gap-2">
                    <x-filament::input.checkbox wire:model.live="keepLastCode" />
                    {{ __('Keep last code in the field') }}
                </label>
                <label class="flex items-center gap-2">
                    <x-filament::input.checkbox wire:model.live="pauseOnReject" />
                    {{ __('Pause after a rejected scan') }}
                </label>
                <label class="flex items-center gap-2">
                    <x-filament::input.checkbox wire:model.live="keepCameraOpen" />
                    {{ __('Keep the camera open') }}
                </label>
            </div>
        </x-filament::section>

        {{-- A new key re-creates the scanner so the switched options take effect. --}}
        <div wire:key="pos-scanner-{{ $this->getOptionsKey() }}">
            {{ $this->form }}
        </div>

        <x-filament::section heading="{{ __('Cart') }}">
            @if ($cart === [])
                <p class="text-sm text-gray-500">{{ __('No items yet.') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-1">{{ __('SKU') }}</th>
                            <th class="py-1">{{ __('Item') }}</th>
                            <th class="py-1 text-right">{{ __('Qty') }}</th>
                            <th class="py-1 text-right">{{ __('Price') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart as $line)
                            <tr wire:key="line-{{ $line['sku'] }}" class="border-t border-gray-200 dark:border-gray-800">
                                <td class="py-1 font-mono">{{ $line['sku'] }}</td>
                                <td class="py-1">{{ $line['name'] }}</td>
                                <td class="py-1 text-right">{{ $line['quantity'] }}</td>
                                <td class="py-1 text-right">{{ number_format($line['price'] * $line['quantity'], 2) }}</td>
                                <td class="py-1 text-right">
                                    <x-filament::link tag="button" color="danger" size="sm" wire:click="removeLine('{{ $line['sku'] }}')">
                                        {{ __('Remove') }}
                                    </x-filament::link>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-300 dark:border-gray-700 font-semibold">
                            <td class="py-2" colspan="3">{{ __('Total') }}</td>
                            <td class="py-2 text-right">{{ number_format($this->getTotal(), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
