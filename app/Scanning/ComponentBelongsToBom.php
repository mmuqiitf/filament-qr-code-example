<?php

namespace App\Scanning;

use App\Models\AssemblyOrder;
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;

/**
 * Piped onto the components step: a component label (CMP-100:0001) must be
 * on the work order's BOM, and no more units than the BOM asks for.
 * Runs after the step's own rules, so the format is already checked, and
 * again on submit.
 */
class ComponentBelongsToBom implements ScanCheckStage
{
    public function check(ScanCheckContext $context): bool|string|null
    {
        $order = AssemblyOrder::findOpen($context->values['order'] ?? null);

        if ($order === null) {
            return 'Scan a work order first.';
        }

        $sku = self::sku($context->code);
        $needed = $order->bom()[$sku] ?? 0;

        if ($needed === 0) {
            return "{$sku} is not on the BOM for {$order->number}.";
        }

        // Live scans see the codes collected so far; submit re-runs see the
        // full list, this code included. Count the others either way.
        $fitted = count(array_filter(
            (array) ($context->values['components'] ?? []),
            fn (mixed $code): bool => $code !== $context->code && self::sku((string) $code) === $sku,
        ));

        return $fitted < $needed ? true : "All {$needed} × {$sku} are already fitted.";
    }

    public static function sku(string $code): string
    {
        return explode(':', $code, 2)[0];
    }
}
