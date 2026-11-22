<x-filament-panels::page>
    {{-- QrCollector calls handleCollectorScan() on this page for each new code. --}}
    <div class="space-y-6">
        {{-- Filament's callout renders its heading as <h4> (out of order under the page's <h1>) and ignores the default slot, so the text goes in the description. --}}
        <x-filament::callout icon="heroicon-o-information-circle" color="info">
            <x-slot name="description">
                <strong class="font-semibold">{{ __('Count a shelf with the batch collector') }}</strong>
                {{ __('Scan every product label on the shelf, e.g. PRD-1001, PRD-1003 (camera or handheld). Each code is looked up as it lands; repeats are ignored and unknown codes are flagged. Submit to record the count and see which products were not seen.') }}
            </x-slot>
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
