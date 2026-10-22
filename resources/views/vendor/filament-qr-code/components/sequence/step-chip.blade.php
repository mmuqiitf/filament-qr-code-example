{{--
    Published from filament-qr-code (php artisan vendor:publish --tag=filament-qr-code-views) and trimmed to this
    one part. Change: a small pin on sticky steps (operator, work order, PO) so operators see what stays for the
    next unit. Keep the Alpine bindings (results, currentFieldIndex, idx, isSticky) the package relies on.
--}}
<div data-qr-part="sequence.step-chip" class="relative">
    <div
        class="fi-qr-sequence-step-chip w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
        :class="results[f.key]
            ? 'bg-success-500 text-white'
            : (currentFieldIndex === idx ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300')"
    >
        <span x-show="!results[f.key]" x-text="idx + 1"></span>
        <svg x-show="results[f.key]" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
    </div>
    <span
        data-mfg-sticky-pin
        x-show="isSticky(f.key)"
        x-cloak
        class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-warning-500 ring-2 ring-white dark:ring-gray-900"
        title="{{ __('Kept for the next unit') }}"
    ></span>
</div>
