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

        $this->afterStateUpdated(function ($component, ?string $state) use ($normalize): void {
            $normalized = $normalize($state);

            if ($normalized !== $state) {
                $component->state($normalized);
            }
        });

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
