<?php

namespace App\Helpers;

class IsoHelper
{
    /**
     * Format ISO class for display
     * Converts "1.2.1" to "[1;2;1]"
     */
    public static function formatIsoClass(string $isoClass): string
    {
        return '[' . str_replace('.', ';', $isoClass) . ']';
    }
}