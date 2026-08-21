<?php

namespace App\Mappers;

/**
 * Maps features from Texas's mill data.
 *
 * Source: State-supplied CSV (no ArcGIS extract needed — all 104 records
 *         have coordinates and the TX ArcGIS map is sourced from our own
 *         historical data).
 * Records: 104 (replaces 106 historical TX records)
 *
 * TX uses verbose SpecificIndustryType values, some of which are
 * intentionally preserved verbatim (TX's own ambiguous labels).
 * Species values are specific species names, not HW/SW — inferSpecies()
 * handles the mapping.
 *
 * Phone2 is mapped to telephone_2 (11.5% populated).
 * Address is a combined string passed to the geocoder.
 */
class TexasMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'TX';
    }

    public function map(array $feature): array
    {
        $props    = $feature['properties'] ?? [];
        $extended = $this->buildExtendedAttributes($props);

        [$species, $speciesDetail] = $this->mapSpecies($props['Species'] ?? null);

        if ($speciesDetail !== null) {
            $extended['species_detail'] = $speciesDetail;
        }

        return array_filter([
            'mill_name'           => $this->clean($props['Company'] ?? null),
            'latitude'            => $this->latitudeFromProperty($props, 'Latitude'),
            'longitude'           => $this->longitudeFromProperty($props, 'Longitude'),
            'physical_address'    => $this->clean($props['Address'] ?? null),
            'physical_state'      => 'TX',
            'county_name'         => $this->clean($props['County'] ?? null),
            'telephone'           => $this->clean($props['Phone1'] ?? null),
            'telephone_2'         => $this->clean($props['Phone2'] ?? null),
            'email'               => $this->clean($props['Email'] ?? null),
            'web_site'            => $this->clean($props['Homepage'] ?? null),
            'type'                => $this->mapType($props['SpecificIndustryType'] ?? null),
            'species'             => $species,
            'extended_attributes' => ! empty($extended) ? $extended : null,
        ], fn ($v) => $v !== null);
    }

    // -------------------------------------------------------------------------

    private function mapType(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        $crosswalk = [
            'Sawmill'
                => 'Sawmill',

            'Post, pole, piling, preservative treating plant'
                => 'Post, Pole, Piling, Preservative Treating Plant',

            // Retained verbatim — TX's ambiguous label
            'Paper mill or chip mill'
                => 'Paper Mill Or Chip Mill',

            'Plywood, veneer, or oriented strandboard mill'
                => null, // multi-type — handled below

            // Retained verbatim — TX's ambiguous label
            'Biomass, wood pellet or landscape organic facility'
                => 'Biomass, Wood Pellet Or Landscape Organic Facility',

            'Mulch'
                => 'Mulch',

            'Firewood'
                => 'Firewood',
        ];

        // Special case: plywood/veneer/OSB → two canonical types
        if ($cleaned === 'Plywood, veneer, or oriented strandboard mill') {
            return $this->pipeJoin(['Veneer / Plywood / Panels', 'OSB']);
        }

        if (\array_key_exists($cleaned, $crosswalk)) {
            $canonical = $crosswalk[$cleaned];
            return $canonical !== null ? $this->titleCase($canonical) : null;
        }

        return null;
    }

    /**
     * Returns [species_string|null, species_detail|null].
     * TX species values are specific species names; inferSpecies() maps them.
     * Any unresolvable portion is returned as species_detail.
     */
    private function mapSpecies(?string $raw): array
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return [null, null];
        }

        $remainder = null;
        $resolved  = $this->inferSpecies($cleaned, $remainder);

        return [
            $this->pipeJoin($resolved),
            $remainder,
        ];
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        if ($v = $this->clean($props['Products'] ?? null)) {
            $extended['product_note'] = $v;
        }

        return $extended;
    }
}
