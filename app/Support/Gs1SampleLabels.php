<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Sample GS1 labels for the GS1 Labels page, the GS1 Receiving demo and the
 * seeder. Dates are relative to today, so the valid labels never go stale.
 * `expected` is the receiving page's rejection message, or null when the
 * label is received.
 *
 * @phpstan-type Gs1Sample array{key: string, title: string, caption: string, value: string, gtin: ?string, symbology: string, expected: ?string}
 */
class Gs1SampleLabels
{
    public const LABEL_ROLLS_GTIN = '08901234567074';

    public const BATTERY_PACK_GTIN = '08901234567081';

    public const WIPES_GTIN = '08901234567098';

    /**
     * @return list<Gs1Sample>
     */
    public static function all(): array
    {
        $today = CarbonImmutable::today();
        $yearAhead = $today->addYear()->format('ymd');
        $expired = $today->subDays(30);

        return [
            [
                'key' => 'label-rolls',
                'title' => 'Case of thermal label rolls',
                'caption' => 'GTIN, best before at month end (day 00), batch, 12.5 kg net, 12 rolls.',
                'value' => '(01)'.self::LABEL_ROLLS_GTIN.'(17)'.$today->addMonths(12)->format('ym').'00(10)L'.$today->format('ym').'A(3103)012500(30)12',
                'gtin' => self::LABEL_ROLLS_GTIN,
                'symbology' => 'gs1-128',
                'expected' => null,
            ],
            [
                'key' => 'battery-pack',
                'title' => 'Serialised battery pack',
                'caption' => 'GTIN, expiry, batch and serial number.',
                'value' => '(01)'.self::BATTERY_PACK_GTIN.'(17)'.$today->addMonths(18)->format('ymd').'(10)BAT-'.$today->format('ym').'(21)SN-0042',
                'gtin' => self::BATTERY_PACK_GTIN,
                'symbology' => 'gs1-128',
                'expected' => null,
            ],
            [
                'key' => 'wipes-pallet',
                'title' => 'Weighed pallet of cleaning wipes',
                'caption' => 'GTIN, expiry, batch and a 248.4 kg net weight.',
                'value' => '(01)'.self::WIPES_GTIN.'(17)'.$today->addMonths(9)->format('ymd').'(10)WP-'.$today->format('ymd').'(3103)248400',
                'gtin' => self::WIPES_GTIN,
                'symbology' => 'gs1-128',
                'expected' => null,
            ],
            [
                'key' => 'bad-check-digit',
                'title' => 'Bad check digit',
                'caption' => 'The GTIN ends in 5 instead of 4. GS1-128 refuses to draw it, so it is printed as plain Code 128.',
                'value' => '(01)08901234567075(17)'.$yearAhead.'(10)LOT-'.$today->format('ym').'B',
                'gtin' => '08901234567075',
                'symbology' => 'code128',
                'expected' => 'Invalid check digit in AI (01)',
            ],
            [
                'key' => 'missing-expiry',
                'title' => 'No expiry date',
                'caption' => 'A valid GS1 label, but receiving requires AI (17).',
                'value' => '(01)'.self::BATTERY_PACK_GTIN.'(10)BAT-'.$today->format('ym'),
                'gtin' => self::BATTERY_PACK_GTIN,
                'symbology' => 'gs1-128',
                'expected' => 'Missing AI (17) USE BY or EXPIRY',
            ],
            [
                'key' => 'expired',
                'title' => 'Expired stock',
                'caption' => 'Expired 30 days ago: the page\'s own check after the GS1 check.',
                'value' => '(01)'.self::WIPES_GTIN.'(17)'.$expired->format('ymd').'(10)WP-OLD',
                'gtin' => self::WIPES_GTIN,
                'symbology' => 'gs1-128',
                'expected' => 'Expired on '.$expired->toDateString().'. Quarantine the stock instead of receiving it.',
            ],
            [
                'key' => 'plain-ean',
                'title' => 'Plain EAN-13',
                'caption' => 'The retail barcode on one roll: a product number, not a GS1 label.',
                'value' => substr(self::LABEL_ROLLS_GTIN, 1),
                'gtin' => null,
                'symbology' => 'ean13',
                'expected' => 'Plain product barcode, not a GS1 label',
            ],
        ];
    }

    /**
     * @return Gs1Sample
     */
    public static function find(string $key): array
    {
        foreach (self::all() as $sample) {
            if ($sample['key'] === $key) {
                return $sample;
            }
        }

        throw new InvalidArgumentException("Unknown GS1 sample [{$key}].");
    }

    /**
     * The samples the receiving page accepts.
     *
     * @return list<Gs1Sample>
     */
    public static function valid(): array
    {
        return array_values(array_filter(self::all(), fn (array $sample): bool => $sample['expected'] === null));
    }
}
