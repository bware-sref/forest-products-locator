<?php

namespace App\Mappers;

use Illuminate\Support\Str;

/**
 * Shared logic for all state mill mappers.
 *
 * Concrete mappers extend this class and implement map() and
 * stateAbbreviation(). They call the helpers defined here to handle
 * the common data quality issues documented in the schema audit.
 */
abstract class AbstractMillMapper implements MillMapperInterface
{
    // -------------------------------------------------------------------------
    // Import filtering
    // -------------------------------------------------------------------------

    /**
     * Default implementation — import all features.
     * No, actually.
     * There are a few cases where we should always not attempt importing a mill.
     * However, since column names differ, I suppose we'll still need to handle them in the state-specific mappers.
     * Override in concrete mappers to apply state-specific filtering.
     */
    public function shouldImport(array $feature): bool
    {
        return true;
    }

    // -------------------------------------------------------------------------
    // String helpers
    // -------------------------------------------------------------------------

    /**
     * Trim a value and return null if the result is empty or whitespace-only.
     */
    protected function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Strip _x000D_ carriage-return artifacts (FL, SC exports)
        $value = str_replace(['_x000D_', "\r", "\n"], '', $value);

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Normalise a value to title case and clean it.
     * Used for mill_types and wood_species values before insert.
     */
    protected function titleCase(?string $value): ?string
    {
        $cleaned = $this->clean($value);

        return $cleaned !== null ? Str::title($cleaned) : null;
    }

    /**
     * Cast a value to a zero-padded 5-digit zip string.
     * Handles int, float (39601.0), and string inputs.
     * Returns null for empty, zero, or uncastable values.
     */
    protected function zip(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === 0.0) {
            return null;
        }

        $int = (int) $value;

        if ($int === 0) {
            return null;
        }

        return str_pad((string) $int, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Normalise a US state value to its 2-letter USPS abbreviation.
     * Handles full names (Virginia, Oklahoma) and common casing variants.
     */
    protected function stateCode(?string $value): ?string
    {
        $cleaned = $this->clean($value);

        if ($cleaned === null) {
            return null;
        }

        $map = [
            'alabama'        => 'AL', 'alaska'         => 'AK',
            'arizona'        => 'AZ', 'arkansas'       => 'AR',
            'california'     => 'CA', 'colorado'       => 'CO',
            'connecticut'    => 'CT', 'delaware'       => 'DE',
            'florida'        => 'FL', 'georgia'        => 'GA',
            'hawaii'         => 'HI', 'idaho'          => 'ID',
            'illinois'       => 'IL', 'indiana'        => 'IN',
            'iowa'           => 'IA', 'kansas'         => 'KS',
            'kentucky'       => 'KY', 'louisiana'      => 'LA',
            'maine'          => 'ME', 'maryland'       => 'MD',
            'massachusetts'  => 'MA', 'michigan'       => 'MI',
            'minnesota'      => 'MN', 'mississippi'    => 'MS',
            'missouri'       => 'MO', 'montana'        => 'MT',
            'nebraska'       => 'NE', 'nevada'         => 'NV',
            'new hampshire'  => 'NH', 'new jersey'     => 'NJ',
            'new mexico'     => 'NM', 'new york'       => 'NY',
            'north carolina' => 'NC', 'north dakota'   => 'ND',
            'ohio'           => 'OH', 'oklahoma'       => 'OK',
            'oregon'         => 'OR', 'pennsylvania'   => 'PA',
            'rhode island'   => 'RI', 'south carolina' => 'SC',
            'south dakota'   => 'SD', 'tennessee'      => 'TN',
            'texas'          => 'TX', 'utah'           => 'UT',
            'vermont'        => 'VT', 'virginia'       => 'VA',
            'washington'     => 'WA', 'west virginia'  => 'WV',
            'wisconsin'      => 'WI', 'wyoming'        => 'WY',
            'district of columbia' => 'DC',
        ];

        $lower = strtolower($cleaned);

        return $map[$lower] ?? strtoupper($cleaned);
    }

    /**
     * Strip trailing * characters and clean.
     * Used for OK company names.
     */
    protected function stripTrailingAsterisks(?string $value): ?string
    {
        return $this->clean(rtrim((string) $value, '*'));
    }

    // -------------------------------------------------------------------------
    // Coordinate helpers
    // -------------------------------------------------------------------------

    /**
     * Extract latitude from a GeoJSON feature.
     * Returns null for 0.0 values (MS FID 71 pattern).
     */
    protected function latitude(array $feature): ?float
    {
        $lat = $feature['geometry']['coordinates'][1] ?? null;

        if ($lat === null || $lat == 0.0) {
            return null;
        }

        return (float) $lat;
    }

    /**
     * Extract longitude from a GeoJSON feature.
     * Returns null for 0.0 values.
     */
    protected function longitude(array $feature): ?float
    {
        $lon = $feature['geometry']['coordinates'][0] ?? null;

        if ($lon === null || $lon == 0.0) {
            return null;
        }

        return (float) $lon;
    }

    /**
     * Get a named latitude property from feature properties (not geometry).
     * Used when the mapper source has explicit Latitude/Lat/Lat_dd columns.
     * Returns null for 0.0 values.
     */
    protected function latitudeFromProperty(array $properties, string $key): ?float
    {
        $val = $properties[$key] ?? null;

        if ($val === null || (float) $val == 0.0) {
            return null;
        }

        return (float) $val;
    }

    /**
     * Get a named longitude property from feature properties.
     */
    protected function longitudeFromProperty(array $properties, string $key): ?float
    {
        $val = $properties[$key] ?? null;

        if ($val === null || (float) $val == 0.0) {
            return null;
        }

        return (float) $val;
    }

    // -------------------------------------------------------------------------
    // Timestamp helpers
    // -------------------------------------------------------------------------

    /**
     * Convert a Unix millisecond timestamp to a UTC datetime string.
     * Used for ArcGIS EditDate fields (GA, NC, AL, OK, TN).
     */
    protected function fromUnixMs(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 0) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromTimestampMs((int) $value)->utc()->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Null a value that is a datetime object or string rather than a
     * meaningful field value. Handles the Excel-corrupted employee/size
     * fields in AL and TN where "5- 9" became "2026-05-09".
     *
     * Returns the original value if it is not a datetime-like string,
     * or null if it looks like an accidental date conversion.
     */
    protected function nullIfDateCorrupted(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Excel DateTime objects come through as Carbon or DateTime instances
        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        $str = (string) $value;

        // Looks like a date string (YYYY-MM-DD or similar) in a non-date field
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str)) {
            return null;
        }

        return $this->clean($str);
    }

    // -------------------------------------------------------------------------
    // Multi-value field helpers
    // -------------------------------------------------------------------------

    /**
     * Join an array of canonical type or species strings into a
     * pipe-delimited string for mills.type or mills.species.
     *
     * Filters nulls and empty strings, deduplicates, applies title case.
     *
     * @param  array<int, string|null>  $values
     */
    protected function pipeJoin(array $values): ?string
    {
        $filtered = array_values(array_unique(array_filter(
            array_map(fn ($v) => $this->titleCase($v), $values),
            fn ($v) => $v !== null && $v !== '',
        )));

        return count($filtered) > 0 ? implode('|', $filtered) : null;
    }

    /**
     * Split a raw multi-value string on the first delimiter found
     * (| then ,) and return an array of trimmed, non-empty values.
     * Used when a source field already contains multiple values.
     *
     * @return array<int, string>
     */
    protected function splitMultiValue(?string $value): array
    {
        if ($this->clean($value) === null) {
            return [];
        }

        $delimiter = str_contains($value, '|') ? '|' : ',';

        return array_values(array_filter(
            array_map('trim', explode($delimiter, $value)),
            fn ($v) => $v !== '',
        ));
    }

    // -------------------------------------------------------------------------
    // Species inference helpers
    // -------------------------------------------------------------------------

    /**
     * Known softwood species keywords (lowercase).
     * Used to infer Softwood from specific species names.
     */
    private const SOFTWOOD_KEYWORDS = [
        'pine', 'syp', 'southern yellow pine', 'yellow pine',
        'cedar', 'eastern red cedar', 'eastern redcedar', 'redcedar',
        'cypress', 'hemlock', 'spruce', 'fir',
    ];

    /**
     * Known hardwood species keywords (lowercase).
     */
    private const HARDWOOD_KEYWORDS = [
        'oak', 'red oak', 'white oak', 'post oak', 'blackjack',
        'poplar', 'yellow poplar', 'walnut', 'hickory', 'ash',
        'maple', 'beech', 'tupelo', 'pecan', 'cottonwood',
        'cherry', 'birch', 'gum',
    ];

    /**
     * Infer canonical wood_species values from a free-text species string.
     *
     * Returns an array of canonical species names (Hardwood, Softwood, Pine,
     * Oak, Cedar, etc.) suitable for pipeJoin(). Unresolvable portions are
     * returned in the $remainder output parameter for storage in
     * extended_attributes.species_type.
     *
     * @param  string|null  $raw        Raw species string from source data
     * @param  string|null  &$remainder Set to the raw value when it cannot
     *                                  be fully resolved; null otherwise
     * @return array<int, string>       Canonical species values
     */
    protected function inferSpecies(?string $raw, ?string &$remainder = null): array
    {
        $remainder = null;

        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return [];
        }

        $lower = strtolower($cleaned);

        // Direct canonical matches
        $directMap = [
            'hardwood'            => ['Hardwood'],
            'softwood'            => ['Softwood'],
            'mixed hardwood'      => ['Hardwood'],
            'all hardwood'        => ['Hardwood'],
            'hardwood (anything)' => ['Hardwood'],
            'mixed softwood'      => ['Softwood'],
            'mixed'               => ['Hardwood', 'Softwood'],
            'all'                 => ['Hardwood', 'Softwood'],
            'any'                 => ['Hardwood', 'Softwood'],
            'anything'            => ['Hardwood', 'Softwood'],
            'hardwood and softwood' => ['Hardwood', 'Softwood'],
            'softwood and hardwood' => ['Hardwood', 'Softwood'],
            'hardwood & softwood'   => ['Hardwood', 'Softwood'],
            'softwood & hardwood'   => ['Hardwood', 'Softwood'],
            'hardwood, softwood'    => ['Hardwood', 'Softwood'],
            'softwood, hardwood'    => ['Hardwood', 'Softwood'],
            'imported'              => [], // AL — neither; caller stores in extended_attributes
        ];

        if (isset($directMap[$lower])) {
            return $directMap[$lower];
        }

        // Species-to-canonical mapping
        $speciesMap = [
            'pine'                => 'Pine',
            'syp'                 => 'Pine',
            'southern yellow pine'=> 'Pine',
            'yellow pine'         => 'Pine',
            'pine only'           => 'Pine',
            'cedar'               => 'Cedar',
            'eastern red cedar'   => 'Cedar',
            'eastern redcedar'    => 'Cedar',
            'redcedar'            => 'Cedar',
            'cedar brush'         => 'Cedar',
            'cypress'             => 'Cypress',
            'oak'                 => 'Oak',
            'red oak'             => 'Oak',
            'white oak'           => 'Oak',
            'post oak'            => 'Oak',
            'blackjack'           => 'Oak',
            'walnut'              => 'Walnut',
            'poplar'              => 'Poplar',
            'yellow poplar'       => 'Poplar',
            'hickory'             => 'Hardwood',
            'ash'                 => 'Hardwood',
            'maple'               => 'Hardwood',
            'soft maple'          => 'Hardwood',
            'hard maple'          => 'Hardwood',
            'beech'               => 'Hardwood',
            'tupelo'              => 'Hardwood',
            'pecan'               => 'Hardwood',
            'cottonwood'          => 'Hardwood',
            'cherry'              => 'Hardwood',
            'mesquite'            => 'Hardwood',
            'hemlock'             => 'Softwood',
            'spruce'              => 'Softwood',
            'fir'                 => 'Softwood',
        ];

        // Split on common delimiters and attempt to map each token
        $tokens   = preg_split('/[,&\/]|\band\b/i', $lower);
        $resolved = [];
        $hasUnresolvable = false;

        foreach ($tokens as $token) {
            $token = trim($token);

            // Strip filler phrases
            $token = preg_replace('/\b(only|please call.*|etc\.?|and more|various)\b.*$/i', '', $token);
            $token = trim($token, " \t\n\r\0\x0B.");

            if ($token === '') {
                continue;
            }

            // Exact match in species map
            if (isset($speciesMap[$token])) {
                $resolved[] = $speciesMap[$token];
                continue;
            }

            // Partial match — token contains a known species keyword
            $matched = false;
            foreach ($speciesMap as $keyword => $canonical) {
                if (str_contains($token, $keyword)) {
                    $resolved[] = $canonical;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                // Could not resolve this token — signal to caller
                $hasUnresolvable = true;
            }
        }

        if ($hasUnresolvable) {
            // Return the original raw value for extended_attributes
            $remainder = $cleaned;
        }

        return array_values(array_unique($resolved));
    }

    // -------------------------------------------------------------------------
    // Raw feature ID helper
    // -------------------------------------------------------------------------

    /**
     * Extract the raw feature ID from a GeoJSON feature.
     * Prefers the top-level 'id' attribute (which matches OBJECTID/FID
     * in ArcGIS responses) over digging into properties.
     */
    protected function rawFeatureId(array $feature): ?string
    {
        if (isset($feature['id'])) {
            return (string) $feature['id'];
        }

        // Fallback: look for OBJECTID or FID in properties
        $props = $feature['properties'] ?? [];

        foreach (['OBJECTID', 'objectid', 'FID', 'fid'] as $key) {
            if (isset($props[$key])) {
                return (string) $props[$key];
            }
        }

        return null;
    }
}
