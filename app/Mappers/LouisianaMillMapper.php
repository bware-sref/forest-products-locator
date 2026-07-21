<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Louisiana's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract
 * Records: 65 (entirely new — 0 historical LA records)
 *
 * LA uses Parish instead of County (handled by the counties table's
 * full_name/type columns). No contact information in source data.
 *
 * The Types field does double duty — 'Softwood' and 'Hardwood' values
 * encode species, not type. For those records:
 *   - mill_wood_species pivot is populated (Softwood / Hardwood)
 *   - mill_mill_type pivot is left empty (no type inferred)
 */
class LouisianaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'LA';
    }

    public function map(array $feature): array
    {
        $props = $feature['properties'] ?? [];

        [$type, $species] = $this->mapTypesField($props['Types'] ?? null);

        return array_filter([
            'mill_name'         => $this->clean($props['Company'] ?? null),
            'latitude'          => $this->latitudeFromProperty($props, 'Lat'),
            'longitude'         => $this->longitudeFromProperty($props, 'Lon'),
            'physical_state'    => 'LA',
            'physical_address'  => $this->clean($props['Address'] ?? null),
            'physical_zip'      => $this->zip($props['Zip'] ?? null),
            'county_name'       => $this->clean($props['Parish'] ?? null),
            'type'              => $type,
            'species'           => $species,
        ], fn ($v) => $v !== null);
    }

    /**
     * Returns [type|null, species|null] from the Types field.
     *
     * 'Softwood' and 'Hardwood' → no type, populate species only.
     * All other values → canonical type, no species signal.
     */
    private function mapTypesField(?string $raw): array
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return [null, null];
        }

        // Species-only values — no mill type inferred
        $speciesOnly = [
            'Softwood' => 'Softwood',
            'Hardwood' => 'Hardwood',
        ];

        if (isset($speciesOnly[$cleaned])) {
            return [null, $this->titleCase($speciesOnly[$cleaned])];
        }

        // Type crosswalk
        $typeCrosswalk = [
            'Chip'      => 'Chip Mill',
            'Pulp&Paper'=> 'Pulp & Paper',
            'Plywood'   => 'Veneer / Plywood / Panels',
            'Panel'     => 'Veneer / Plywood / Panels',
            'Pole'      => 'Post & Pole',
            'OSB'       => 'OSB',
            'Pellet'    => 'Pellet',
            'EWP'       => 'Engineered Wood Products',
        ];

        $canonical = $typeCrosswalk[$cleaned] ?? null;

        return [
            $canonical !== null ? $this->titleCase($canonical) : null,
            null,
        ];
    }
}
