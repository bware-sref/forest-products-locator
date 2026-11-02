<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * latitude/longitude are DOUBLE columns. Reads come back as floats (or null); writes accept numbers or numeric
 * strings, and anything blank or non-numeric (e.g., '' from an import or a lookup that found nothing) is stored
 * as null, which the database would otherwise reject.
 */
trait HasCoordinates
{
    public function initializeHasCoordinates(): void
    {
        // the float cast also makes isDirty() compare values numerically, so 33.95 and '33.95' aren't a change
        $this->mergeCasts([
            'latitude' => 'float',
            'longitude' => 'float',
        ]);
    }

    public static function toCoordinate(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    protected function latitude(): Attribute
    {
        return Attribute::make(set: fn (mixed $value) => self::toCoordinate($value));
    }

    protected function longitude(): Attribute
    {
        return Attribute::make(set: fn (mixed $value) => self::toCoordinate($value));
    }
}
