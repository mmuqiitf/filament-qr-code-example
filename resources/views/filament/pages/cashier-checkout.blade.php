<x-filament-panels::page>
    <div
        class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
        x-on:qr-collector-item-added.window="$wire.scanProduct($event.detail.code)"
        x-on:qr-wedge-scanned.window="$wire.scanProduct($event.detail.value)"
    >
        {{-- Left: Scanner Station & Cart Items (7 cols) --}}
        <div class="lg:col-span-7 space-y-6">
            {{-- Station Header --}}
            <x-filament::section compact>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
                            {{ __('Cashier Register') }}
                        </span>
                        <x-filament::badge color="primary">
                            {{ $this->cashierBadge }}
                        </x-filament::badge>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::input.wrapper prefix="Member:">
                            <x-filament::input
                                type="text"
                                wire:model="customerCode"
                                placeholder="Member code..."
                            />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </x-filament::section>

            {{-- Rapid Scan Bar --}}
            <x-filament::section
                icon="heroicon-o-qr-code"
                heading="{{ __('Hardware / Barcode Scanner Input') }}"
                description="{{ __('Scan barcode or type SKU and press Enter') }}"
            >
                {{-- Package smoke-test: wedge listener (hidden) + continuous camera collector --}}
                {{ $this->form }}

                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-filament::input.wrapper class="flex-1">
                            <x-filament::input
                                type="text"
                                wire:model="scanInput"
                                wire:keydown.enter="handleManualScan"
                                autofocus
                                placeholder="Scan SKU (e.g. PRD-1001) or Barcode (e.g. 8901234567890)..."
                            />
                        </x-filament::input.wrapper>

                        <x-filament::button wire:click="handleManualScan">
                            {{ __('Add') }}
                        </x-filament::button>
                    </div>

                    {{-- Demo Quick Buttons for instant testing --}}
                    <div class="pt-3 border-t border-gray-200 dark:border-white/10">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-2">
                            {{ __('Quick Demo Barcodes (Click to simulate scan):') }}
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::button size="xs" color="gray" wire:click="scanProduct('PRD-1001')">
                                PRD-1001 (Mouse)
                            </x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="scanProduct('PRD-1002')">
                                PRD-1002 (Keyboard)
                            </x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="scanProduct('PRD-1003')">
                                PRD-1003 (USB Hub)
                            </x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="scanProduct('8901234567893')">
                                8901234567893 (Headphones)
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Cart Items --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <span>{{ __('Current Transaction Items') }}</span>
                        <x-filament::badge color="primary">
                            {{ count($cart) }} {{ __('unique') }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    @if(count($cart) > 0)
                        <x-filament::button
                            size="xs"
                            color="danger"
                            wire:click="clearCart"
                        >
                            {{ __('Clear Cart') }}
                        </x-filament::button>
                    @endif
                </x-slot>

                @if(count($cart) === 0)
                    <x-filament::empty-state
                        icon="heroicon-o-shopping-cart"
                        icon-color="gray"
                        heading="{{ __('No products in cart yet') }}"
                        description="{{ __('Use the barcode scanner or demo buttons above to add items.') }}"
                    />
                @else
                    <div class="divide-y divide-gray-200 dark:divide-white/10 -mx-6 -mb-6">
                        @foreach($cart as $index => $item)
                            <div class="px-6 py-3.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <div class="space-y-0.5">
                                    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $item['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $item['sku'] }} &bull; ${{ number_format($item['price'], 2) }} each
                                    </p>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1 border border-gray-200 dark:border-white/10 rounded-lg p-0.5 bg-gray-50 dark:bg-white/5">
                                        <x-filament::icon-button
                                            icon="heroicon-m-minus"
                                            size="xs"
                                            color="gray"
                                            wire:click="decrementQuantity({{ $index }})"
                                        />
                                        <span class="w-8 text-center text-xs font-bold text-gray-950 dark:text-white">
                                            {{ $item['quantity'] }}
                                        </span>
                                        <x-filament::icon-button
                                            icon="heroicon-m-plus"
                                            size="xs"
                                            color="gray"
                                            wire:click="incrementQuantity({{ $index }})"
                                        />
                                    </div>

                                    <div class="w-20 text-right">
                                        <span class="text-sm font-bold text-gray-950 dark:text-white font-mono">
                                            ${{ number_format($item['subtotal'], 2) }}
                                        </span>
                                    </div>

                                    <x-filament::icon-button
                                        icon="heroicon-m-trash"
                                        color="danger"
                                        size="xs"
                                        wire:click="removeItem({{ $index }})"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>

        {{-- Right: Order Summary & Payment Checkout (5 cols) --}}
        <div class="lg:col-span-5 space-y-6">
            <x-filament::section heading="{{ __('Order Summary') }}">
                {{-- Price breakdown --}}
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>{{ __('Subtotal') }}</span>
                        <span class="font-mono font-medium text-gray-950 dark:text-white">${{ number_format($this->subtotal, 2) }}</span>
                    </div>

                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>{{ __('Estimated Tax (8%)') }}</span>
                        <span class="font-mono font-medium text-gray-950 dark:text-white">${{ number_format($this->taxAmount, 2) }}</span>
                    </div>

                    <div class="pt-3 border-t border-gray-200 dark:border-white/10 flex justify-between items-baseline">
                        <span class="text-base font-bold text-gray-950 dark:text-white">{{ __('Total Due') }}</span>
                        <span class="text-2xl font-extrabold text-primary-600 dark:text-primary-400 font-mono">
                            ${{ number_format($this->total, 2) }}
                        </span>
                    </div>
                </div>

                {{-- Payment Method Selection --}}
                <div class="pt-4 border-t border-gray-200 dark:border-white/10 space-y-2">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider block">
                        {{ __('Payment Method') }}
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            wire:click="$set('paymentMethod', 'cash')"
                            class="border rounded-xl p-2.5 text-center text-xs font-medium transition cursor-pointer {{ $paymentMethod === 'cash' ? 'border-primary-500 bg-primary-500/10 text-primary-600 dark:text-primary-400 ring-1 ring-primary-500' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5' }}"
                        >
                            💵 {{ __('Cash') }}
                        </button>
                        <button
                            type="button"
                            wire:click="$set('paymentMethod', 'card')"
                            class="border rounded-xl p-2.5 text-center text-xs font-medium transition cursor-pointer {{ $paymentMethod === 'card' ? 'border-primary-500 bg-primary-500/10 text-primary-600 dark:text-primary-400 ring-1 ring-primary-500' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5' }}"
                        >
                            💳 {{ __('Card') }}
                        </button>
                        <button
                            type="button"
                            wire:click="$set('paymentMethod', 'qr_pay')"
                            class="border rounded-xl p-2.5 text-center text-xs font-medium transition cursor-pointer {{ $paymentMethod === 'qr_pay' ? 'border-primary-500 bg-primary-500/10 text-primary-600 dark:text-primary-400 ring-1 ring-primary-500' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5' }}"
                        >
                            📱 {{ __('QR Pay') }}
                        </button>
                    </div>
                </div>

                {{-- Complete Order Action --}}
                <div class="pt-4">
                    <x-filament::button
                        wire:click="completeCheckout"
                        size="xl"
                        class="w-full"
                        :disabled="count($cart) === 0"
                    >
                        {{ __('Complete Checkout ($' . number_format($this->total, 2) . ')') }}
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
