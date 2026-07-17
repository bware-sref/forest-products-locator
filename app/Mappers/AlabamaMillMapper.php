<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Alabama's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract
 * Records: 494 total; filtered to PriSec = 'Primary' → 113 records.
 *          Filtering happens in ProcessArcGisImport before the mapper
 *          is called — the mapper assumes it only receives Primary records.
 *
 * Coordinates are WGS84 stored in explicit Latitude/Longitude properties
 * (added during extraction, not from GeoJSON geometry).
 *
 * Employee field may contain datetime objects (Excel corruption of range
 * strings like "5- 9" → date). nullIfDateCorrupted() handles this.
 *
 * WoodType 'Imported' maps to neither species — stored in extended_attributes.
 */
class AlabamaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'AL';
    }

    /**
     * AL: Primary mills only — Secondary mills (furniture, cabinets, trusses,
     * pallets, etc.) are out of scope for this dataset.
     */
    public function shouldImport(array $feature): bool
    {
        $priSec = strtolower(trim($feature['properties']['PriSec'] ?? ''));

        return $priSec === 'primary';
    }

    public function map(array $feature): array
    {
        $props    = $feature['properties'] ?? [];
        $extended = $this->buildExtendedAttributes($props);

        return array_filter([
            'mill_name'           => $this->clean($props['Company'] ?? null),
            'latitude'            => $this->latitudeFromProperty($props, 'Latitude'),
            'longitude'           => $this->longitudeFromProperty($props, 'Longitude'),
            'physical_address'    => $this->clean($props['Address'] ?? null),
            'physical_city'       => $this->clean($props['City'] ?? null),
            'physical_state'      => 'AL',
            'physical_zip'        => $this->zip($props['ZIP'] ?? null),
            'county_name'         => $this->clean($props['County'] ?? null),
            'telephone'           => $this->clean($props['Phone'] ?? null),
            'web_site'            => $this->clean($props['Website'] ?? null),
            'size'                => $this->nullIfDateCorrupted($props['Employee'] ?? null),
            'type'                => $this->mapType($props['IndSectors'] ?? null),
            'species'             => $this->mapSpecies($props),
            'modification_date'   => $this->fromUnixMs($props['last_edited_date'] ?? null),
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
            'Sawmills'                              => 'Sawmill',
            'Pole and Piling'                       => 'Post & Pole',
            'Pulp, Paper, and Paperboards'          => 'Pulp, Paper, And Paperboards',
            'Veneer And Plywood Manufacturing'      => 'Veneer / Plywood / Panels',
            'Reconstituted Wood Products'           => 'Reconstituted Wood Products',
            'Engineered Wood Member'                => 'Engineered Wood Products',
            'Wood Preservation'                     => 'Wood Preservation',
            'Pellet'                                => 'Pellet',
            'Miscellaneous Wood Products'           => 'Other',
            'Wood Container and Pallet Manufacturing' => 'Wood Container And Pallet Manufacturing',
        ];

        $canonical = $crosswalk[$cleaned] ?? null;

        return $canonical !== null ? $this->titleCase($canonical) : null;
    }

    private function mapSpecies(array $props): ?string
    {
        $species = [];

        $rawWoodType = $this->clean($props['WoodType'] ?? null);

        if ($rawWoodType !== null) {
            $woodTypeMap = [
                'Hardwood'             => ['Hardwood'],
                'Softwood'             => ['Softwood'],
                'Hardwood and Softwood'=> ['Hardwood', 'Softwood'],
                'Imported'             => [], // stored in extended_attributes
            ];

            $species = array_merge(
                $species,
                $woodTypeMap[trim($rawWoodType)] ?? []
            );
        }

        return $this->pipeJoin($species);
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        // WoodType = 'Imported' has no canonical species mapping
        if (strtolower(trim($props['WoodType'] ?? '')) === 'imported') {
            $extended['species_detail'] = 'Imported';
        }

        // Specific species names (e.g. "Softwood, SYP")
        if ($v = $this->clean($props['SpeciesUse'] ?? null)) {
            $extended['species_detail'] = $v;
        }

        if ($v = $this->clean($props['WoodMatUse'] ?? null)) {
            $extended['wood_material_used'] = $v;
        }

        if ($v = $this->clean($props['IndGroup'] ?? null)) {
            $extended['naics_group'] = $v;
        }

        // Secondary type signal — store for reference
        if ($v = $this->clean($props['Products'] ?? null)) {
            $extended['products_raw'] = $v;
        }

        return $extended;
    }
}
