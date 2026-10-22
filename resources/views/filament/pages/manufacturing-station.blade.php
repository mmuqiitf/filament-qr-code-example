<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::callout
            icon="heroicon-o-information-circle"
            color="info"
            heading="{{ __('Build a unit with a handheld scanner') }}"
        >
            <p class="text-xs mt-1">
                {{ __('Scan your badge (OP-001), a work order (WO-5001), then one label per component (CMP-100:0001, CMP-100:0002 and CMP-200:0001) and DONE. WO-5002 is a rework order and asks for a reason such as RW-07. Badge and work order stay for the next unit. Print the labels from the QR Labels page.') }}
            </p>
        </x-filament::callout>

        <div class="flex flex-wrap gap-2">
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-path" wire:click="restartUnit">
                {{ __('Restart unit') }}
            </x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-cube" wire:click="backToComponents">
                {{ __('Back to components') }}
            </x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-user" wire:click="changeOperator">
                {{ __('Change operator') }}
            </x-filament::button>
            {{-- Runtime control from the browser: window events aimed at the sequence by its state path. --}}
            <x-filament::button size="sm" color="warning" icon="heroicon-o-pause" x-on:click="$dispatch('qr-sequence-pause', { field: 'data.station' })">
                {{ __('Pause scanning') }}
            </x-filament::button>
            <x-filament::button size="sm" color="success" icon="heroicon-o-play" x-on:click="$dispatch('qr-sequence-resume', { field: 'data.station' })">
                {{ __('Resume') }}
            </x-filament::button>
        </div>

        <form wire:submit="submit" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-check-badge">
                {{ __('Build unit') }}
            </x-filament::button>
        </form>

        <x-filament::section heading="{{ __('Station activity') }}" compact>
            @if ($activity === [])
                <p class="text-sm text-gray-500">{{ __('Nothing scanned yet.') }}</p>
            @else
                <ul class="space-y-1 text-sm" data-mfg-activity>
                    @foreach ($activity as $entry)
                        <li wire:key="activity-{{ $loop->index }}-{{ md5($entry['text']) }}" @class(['text-danger-600 dark:text-danger-400' => $entry['rejected']])>
                            <span class="font-mono text-xs text-gray-500">{{ $entry['step'] }}</span>
                            {{ $entry['text'] }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
