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

        $this->scanFormat(function (?string $rawValue) use ($prefix): ?string {
            if ($rawValue === null) {
                return null;
            }

            $trimmed = strtoupper(trim($rawValue));
            $upperPrefix = strtoupper($prefix);
            if ($upperPrefix !== '' && str_starts_with($trimmed, $upperPrefix)) {
                $trimmed = substr($trimmed, strlen($upperPrefix));
            }

            return $trimmed;
        });

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
