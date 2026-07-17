<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Mississippi's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract
 * Records: 102 (replaces 112 historical MS records)
 *
 * Clean, discrete address fields. No contact information.
 * The 'address' property is a combined string redundant with the
 * discrete fields (verified) — it is discarded.
 *
 * One record (FID 71, Lincoln Tie & Timber) has lat/lon = 0.0 and
 * blank species. GeocodeMill will resolve coordinates from address.
 */
class MississippiMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'MS';
    }

    public function map(array $feature): array
    {
        $props = $feature['properties'] ?? [];

        return array_filter([
            'mill_name'         => $this->clean($props['company_na'] ?? null),
            'latitude'          => $this->latitudeFromProperty($props, 'Lat_dd'),
            'longitude'         => $this->longitudeFromProperty($props, 'Long_dd'),
            'physical_address'  => $this->clean($props['physical_a'] ?? null),
            'physical_city'     => $this->clean($props['city'] ?? null),
            'physical_state'    => $this->stateCode($props['state'] ?? 'MS'),
            'physical_zip'      => $this->zip($props['zip'] ?? null),
            'county_name'       => $this->clean($props['county'] ?? null),
            'type'              => $this->mapType($props['mill_Type'] ?? null),
            'species'           => $this->mapSpecies($props['species'] ?? null),
        ], fn ($v) => $v !== null);
    }

    private function mapType(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        // Pallet / Cross-Tie splits into two pivot rows
        if ($cleaned === 'Pallet / Cross-Tie') {
            return $this->pipeJoin(['Pallet', 'Crosstie']);
        }

        $crosswalk = [
            'Sawmill'       => 'Sawmill',
            'Chip'          => 'Chip Mill',
            'Pole'          => 'Post & Pole',
            'Plywood'       => 'Veneer / Plywood / Panels',
            'Pulp Paper'    => 'Pulp & Paper',
            'Energy Product'=> 'Energy Product',
            'OSB'           => 'OSB',
            'Particle Board'=> 'Particle Board',
            'Other'         => 'Other',
        ];

        $canonical = $crosswalk[$cleaned] ?? null;

        return $canonical !== null ? $this->titleCase($canonical) : null;
    }

    private function mapSpecies(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        $crosswalk = [
            'Softwood' => ['Softwood'],
            'Hardwood' => ['Hardwood'],
            'Mixed'    => ['Hardwood', 'Softwood'],
        ];

        $values = $crosswalk[$cleaned] ?? null;

        return $values !== null ? $this->pipeJoin($values) : null;
    }
}
