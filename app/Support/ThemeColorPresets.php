<?php

namespace App\Support;

/**
 * A curated palette instead of a free color picker — every entry here has
 * been checked against Institute::sidebarStyle()'s contrast heuristic and
 * sits clearly on one side (not a borderline value), so no combination an
 * institute picks can ever reproduce the blue-sidebar/blue-text readability
 * bug. The single source of truth for both the form's validation rule and
 * the swatches rendered in it, so the two can never drift apart.
 */
class ThemeColorPresets
{
    private const PRESETS = [
        ['label' => 'Classic Dark', 'color' => '#212529'],
        ['label' => 'Ocean Blue', 'color' => '#0a3d62'],
        ['label' => 'Forest Green', 'color' => '#14532d'],
        ['label' => 'Royal Purple', 'color' => '#3b0764'],
        ['label' => 'Slate Gray', 'color' => '#334155'],
        ['label' => 'Burgundy', 'color' => '#5c1a1a'],
        ['label' => 'Soft Sand', 'color' => '#f5e6d3'],
        ['label' => 'Sky Mist', 'color' => '#dbeafe'],
    ];

    /**
     * @return array<int, array{label: string, color: string}>
     */
    public static function all(): array
    {
        return self::PRESETS;
    }

    /**
     * @return array<int, string>
     */
    public static function colors(): array
    {
        return array_column(self::PRESETS, 'color');
    }
}
