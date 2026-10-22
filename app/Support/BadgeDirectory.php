<?php

namespace App\Support;

/**
 * Operators come from a badge system, not the app database: a plain class
 * stands in for an HR or access-control API. The station finds badges with
 * ScanStep::lookupUsing(), which accepts any non-Eloquent source.
 */
class BadgeDirectory
{
    /**
     * @var array<string, array{badge: string, name: string, role: string, active: bool}>
     */
    private const BADGES = [
        'OP-001' => ['badge' => 'OP-001', 'name' => 'Siti Rahma', 'role' => 'Assembler', 'active' => true],
        'OP-002' => ['badge' => 'OP-002', 'name' => 'Budi Santoso', 'role' => 'Line lead', 'active' => true],
        'OP-009' => ['badge' => 'OP-009', 'name' => 'Former Operator', 'role' => 'Assembler', 'active' => false],
    ];

    /**
     * An active operator, or null.
     *
     * @return array{badge: string, name: string, role: string, active: bool}|null
     */
    public function find(string $badge): ?array
    {
        $operator = self::BADGES[$badge] ?? null;

        return ($operator['active'] ?? false) ? $operator : null;
    }

    /**
     * @return list<array{badge: string, name: string, role: string, active: bool}>
     */
    public function active(): array
    {
        return array_values(array_filter(self::BADGES, fn (array $operator): bool => $operator['active']));
    }
}
