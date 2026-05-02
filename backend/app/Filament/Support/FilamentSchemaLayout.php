<?php

namespace App\Filament\Support;

use Filament\Schemas\Schema;

/**
 * Root Filament schemas default to multi-column layouts at lg+ breakpoints, which places
 * sibling Sections side-by-side. Force a single vertical stack so each Section/card spans full width.
 */
final class FilamentSchemaLayout
{
    public static function stackSections(Schema $schema): Schema
    {
        return $schema->columns([
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
            'xl' => 1,
            '2xl' => 1,
        ]);
    }
}
