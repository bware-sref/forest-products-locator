<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Tennessee's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract (URL found via SC map)
 * Records: 243 (replaces 319 historical TN records)
 *
 * TN is the most data-rich state — 15 species boolean columns, 11 product
 * boolean columns, employee ranges, portable flag, verbose Type strings.
 *
 * Phys_County is a numeric FIPS code — stored in county_name as-is;
 * ProcessMillState resolves it via the US Counties dataset.
 *
 * Employee field may contain datetime objects (Excel corruption).
 * nullIfDateCorrupted() handles this.
 *
 * Portable = 'Yes' adds a 'Portable Sawmill' pivot row alongside whatever
 * type the Type field maps to.
 *
 * TN Type values are retained verbatim for energy/firewood/log variants
 * per audit decision (do not collapse into Energy Product / Firewood / etc.).
 */
class TennesseeMillMapper extends AbstractMillMapper
{
    /**
     * TN's 15 species boolean columns and their display names.
     * Stored as extended_attributes.species_flags JSON object.
     */
    private const array SPECIES_FLAGS = [
        'Cypress', 'Cedar', 'Pine', 'Hemlock',
        'SoftMaple', 'HardMaple', 'Tupelo', 'Beech',
        'RedOak', 'WhiteOak', 'Hickory', 'Walnut',
        'YellowPoplar', 'Ash', 'OtherHdwds',
    ];

    /**
     * TN's 11 product boolean columns.
     * Stored as extended_attributes.product_flags JSON object.
     */
    private const array PRODUCT_FLAGS = [
        'RoundLogs', 'SawnDimLumber', 'Pallet', 'Crosstie', 'Cant',
        'HandlesHandleBlanks', 'BarrelStave', 'Posts', 'Chips', 'Sawdust', 'Mulch',
    ];

    public function stateAbbreviation(): string
    {
        return 'TN';
    }

    public function map(array $feature): array
    {
        $props    = $feature['properties'] ?? [];
        $extended = $this->buildExtendedAttributes($props);

        return array_filter([
            'mill_name'           => $this->clean($props['Company_Name'] ?? null),
            'latitude'            => $this->latitude($feature),
            'longitude'           => $this->longitude($feature),
            'physical_address'    => $this->clean($props['Phys_Add'] ?? null),
            'physical_city'       => $this->clean($props['Phys_City'] ?? null),
            'physical_state'      => $this->stateCode($props['Phys_St'] ?? 'TN'),
            'physical_zip'        => $this->zip($props['Phys_Zip'] ?? null),
            // Phys_County is a numeric FIPS code — stored as-is for ProcessMillState
            'county_name'         => isset($props['Phys_County']) && $props['Phys_County'] !== null
                                        ? (string) (int) $props['Phys_County']
                                        : null,
            'mailing_address'     => $this->clean($props['Mail_Add'] ?? null),
            'mailing_city'        => $this->clean($props['Mail_City'] ?? null),
            'mailing_state'       => $this->stateCode($props['Mail_St'] ?? null),
            'mailing_zip'         => $this->zip($props['Mail_Zip'] ?? null),
            'telephone'           => $this->clean($props['Comp_Phone'] ?? null),
            'web_site'            => $this->clean($props['Website'] ?? null),
            'size'                => $this->nullIfDateCorrupted($props['Employees'] ?? null),
            'type'                => $this->mapType($props),
            'species'             => $this->mapSpecies($props['SpeciesGroup'] ?? null),
            'modification_date'   => $this->fromUnixMs($props['EditDate'] ?? null),
            'extended_attributes' => ! empty($extended) ? $extended : null,
        ], fn ($v) => $v !== null);
    }

    // -------------------------------------------------------------------------

    private function mapType(array $props): ?string
    {
        $types = [];

        $rawType = $this->clean($props['Type'] ?? null);

        if ($rawType !== null) {
            $crosswalk = [
                'Saw mill (includes cooperage/stave, handle mills)' => 'Sawmill',
                'Pulp/Paper mill'                                   => 'Pulp & Paper',
                'Chip mill'                                         => 'Chip Mill',
                'Veneer/plywood mill'                               => 'Veneer / Plywood / Panels',
                // Retained verbatim per audit decision
                'Industrial fuelwood/Biomass/Energy'                => 'Industrial Fuelwood/Biomass/Energy',
                'Biomass/Energy plant'                              => 'Biomass/Energy Plant',
                'Export yard'                                       => 'Export Yard',
                'Log yard'                                          => 'Log Yard',
                'Firewood'                                          => 'Firewood',
                'Fuelwood'                                          => 'Fuelwood',
                'Composite'                                         => 'Composite',
            ];

            $canonical = $crosswalk[$rawType] ?? null;

            if ($canonical !== null) {
                $types[] = $this->titleCase($canonical);
            }
        }

        // Portable = 'Yes' adds Portable Sawmill alongside the main type
        $portable = $this->clean($props['Portable'] ?? null);

        if ($portable !== null && strtolower($portable) === 'yes') {
            $types[] = 'Portable Sawmill';
        }

        return $this->pipeJoin($types);
    }

    private function mapSpecies(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null || strtolower($cleaned) === 'not available') {
            return null;
        }

        $species = [];

        foreach (explode('&', $cleaned) as $token) {
            $token = trim($token);

            match (strtolower($token)) {
                'hardwood' => $species[] = 'Hardwood',
                'softwood' => $species[] = 'Softwood',
                default    => null,
            };
        }

        return $this->pipeJoin($species);
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        // Species boolean flags → JSON object
        $speciesFlags = [];
        foreach (self::SPECIES_FLAGS as $col) {
            if (isset($props[$col]) && $props[$col]) {
                $speciesFlags[$col] = true;
            }
        }
        if (! empty($speciesFlags)) {
            $extended['species_flags'] = $speciesFlags;
        }

        if ($v = $this->clean($props['SpeciesList'] ?? null)) {
            $extended['species_detail'] = $v;
        }

        // Product boolean flags → JSON object
        $productFlags = [];
        foreach (self::PRODUCT_FLAGS as $col) {
            if (isset($props[$col]) && $props[$col]) {
                $productFlags[$col] = true;
            }
        }
        if (! empty($productFlags)) {
            $extended['product_flags'] = $productFlags;
        }

        if ($v = $this->clean($props['ProductList'] ?? null)) {
            $extended['product_note'] = $v;
        }

        return $extended;
    }
}
