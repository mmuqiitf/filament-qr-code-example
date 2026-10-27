<x-filament-panels::page>
    {{-- QrCollector reports each new code through the documented window event; forward it to the lookup. --}}
    <div class="space-y-6" x-data x-on:qr-collector-item-added.window="$wire.handleCollectorScan($event.detail.code)">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Count a shelf with the batch collector') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Scan every product label on the shelf, e.g. PRD-1001, PRD-1003 (camera or handheld). Each code is looked up as it lands; repeats are ignored and unknown codes are flagged. Submit to record the count and see which products were not seen.') }}
            </p>
        </x-filament::callout>

        <form wire:submit="submit" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-clipboard-document-check">
                {{ __('Record count') }}
            </x-filament::button>
        </form>

        <x-filament::section heading="{{ __('Recent counts') }}" compact>
            @forelse ($this->getRecentCounts() as $count)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm border-b last:border-b-0 border-gray-100 dark:border-gray-800">
                    <span class="font-semibold">{{ $count->location }}</span>
                    <span class="text-xs text-gray-500">
                        {{ count($count->counted) }} {{ __('counted') }} · {{ count($count->missing) }} {{ __('not seen') }}@if ($count->missing): {{ implode(', ', $count->missing) }}@endif
                    </span>
                </div>
            @empty
                <p class="text-xs text-gray-400">{{ __('No counts yet.') }}</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
