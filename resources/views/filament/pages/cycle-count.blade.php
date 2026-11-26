<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout icon="heroicon-o-information-circle" color="info">
            <x-slot name="description">
                <strong class="font-semibold">{{ __('Cycle count with quantities, CSV, and item cap') }}</strong>
                {{ __('Rescan PRD-1001, PRD-1002, or PRD-1003 to increment quantities instead of rejecting duplicates, adjust counts inline, import/export CSV rows, or test the 5-SKU batch cap.') }}
            </x-slot>
        </x-filament::callout>

        <form wire:submit="submit" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-calculator">
                {{ __('Save cycle count') }}
            </x-filament::button>
        </form>

        <x-filament::section heading="{{ __('Recorded cycle counts') }}" compact>
            @forelse ($recordedCounts as $count)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm border-b last:border-b-0 border-gray-100 dark:border-gray-800">
                    <span class="font-semibold">{{ $count['location'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $count['sku_count'] }} {{ __('SKU(s)') }} · {{ $count['total_units'] }} {{ __('unit(s)') }}
                        ({{ collect($count['items'])->map(fn (array $item): string => "{$item['code']} ×{$item['qty']}")->implode(', ') }})
                    </span>
                </div>
            @empty
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('No cycle counts recorded in this session yet.') }}</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
