<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class CustomFormattedScanner extends QrScanner
{
    protected string $prefix = '';

    public function prefixFilter(string $prefix): static
    {
        $this->prefix = $prefix;

        $normalize = function (?string $rawValue) use ($prefix): ?string {
            if ($rawValue === null) {
                return null;
            }

            $trimmed = strtoupper(trim($rawValue));
            $upperPrefix = strtoupper($prefix);
            if ($upperPrefix !== '' && str_starts_with($trimmed, $upperPrefix)) {
                $trimmed = substr($trimmed, strlen($upperPrefix));
            }

            return $trimmed;
        };

        $this->scanFormat($normalize);

        // Live scans (camera/hardware/typing) normalize here; scanFormat() above
        // stays programmatic-only for formatScannedValue()/triggerOnScan() flows.
        $this->normalizeUsing($normalize);

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
