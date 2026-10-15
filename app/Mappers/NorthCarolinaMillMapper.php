<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from North Carolina's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract
 * Records: 31 (replaces 246 historical NC records)
 *
 * NC is the most minimal dataset — name, coordinates, and one type
 * field (OP1) only. No address, no contact, no county, no species.
 */
class NorthCarolinaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'NC';
    }

    public function map(array $feature): array
    {
        $props = $feature['properties'] ?? [];

        return array_filter([
            'mill_name' => $this->clean($props['Company'] ?? null),
            /**
             * Pass the entire feature just in case we have to fallback to coordinates.
             */
            'latitude' => $this->latitude($feature),
            'longitude' => $this->longitude($feature),
            /**
             * Why doesn't the mapper use the stateAbbreviation() method?
             */
            'physical_state' => 'NC',
            'type' => $this->mapType($props['OP1'] ?? null),
            'modification_date' => $this->fromUnixMs($props['EditDate'] ?? null),
        ], fn($v) => $v !== null);
    }

    private function mapType(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        $crosswalk = [
            'Pulp & Paper Mill' => 'Pulp & Paper',
            'Plywood/Veneer Mill' => 'Veneer / Plywood / Panels',
            'OSB Mfg Plant' => 'OSB',
            'Pellet Mill' => 'Pellet',
            'Commercial Firewood' => 'Firewood',
            'Mulch Plant' => 'Mulch',
            'Fiberboard Plant (MDF)' => 'Fiberboard Plant (MDF)',
            'Log Export Yard' => 'Log Export',

            // 'Sawmill'               => 'Sawmill',
            // 'Chip Mill'             => 'Chip Mill',
            // 'Firewood'              => 'Firewood',
            // 'Particle Board'        => 'Particle Board',
            // 'Electrical Power Plant' => 'Electrical Power Plant',
        ];

        /**
         * Why return nothing when would could use the original value instead?!?
         * This also lets us get rid of the values that map back to themselves.
         * AND!
         * It lets us just comment out incorrect mappings instead of correcting them!
         */
        // $canonical = $crosswalk[$cleaned] ?? null;
        $canonical = $crosswalk[$cleaned] ?? $cleaned;

        return $canonical !== null ? $this->titleCase($canonical) : null;
    }

    /**
     * Maps the Lat column to latitude.
     * Falls back to parent method (coordinates[1]) if Lat column empty.
     * @param array $feature
     * @return float|null
     */
    protected function latitude(array $feature): ?float
    {
        return (float) $feature['properties']['Lat'] ?: parent::latitude($feature);
    }

    /**
     * Maps the Long column to longitude.
     * Falls back to parent method (coordinates[0]) if Long column empty.
     * @param array $feature
     * @return float|null
     */
    protected function longitude(array $feature): ?float
    {
        return (float) $feature['properties']['Long'] ?: parent::longitude($feature);
    }

    /**
     * Probably a cleaner way to handle this.
     * Basically, if the mill doesn't have a name, or it's missing lat & long, we can't do much with it.
     * @param array $feature
     * @return bool
     */
    public function shouldImport(array $feature): bool
    {
        $shouldImport = !blank($feature['properties']['Company']) && !blank($this->latitude($feature)) && !blank($this->longitude($feature));
        /**
         * FFS, parent::shouldImport() always returns true.
         * Why does this mapper do this but none of the others?
         */
        return $shouldImport && parent::shouldImport($feature);
    }
}
