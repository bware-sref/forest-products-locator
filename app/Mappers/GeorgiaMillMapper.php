<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Georgia's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract
 * Records: 189 (replaces 206 historical GA records)
 *
 * GA has the richest schema of the ArcGIS-sourced states — full address,
 * contact, three product fields, species abbreviations, size codes, and
 * several extended fields.
 *
 * Products are split across Product1/2/3 — each maps independently.
 * Species uses two-letter abbreviations: HW, SW, HW & SW.
 * Mill_Size A–H is stored verbatim in mills.size.
 */
class GeorgiaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'GA';
    }

    public function map(array $feature): array
    {
        $props    = $feature['properties'] ?? [];
        $extended = $this->buildExtendedAttributes($props);

        return array_filter([
            'mill_name'           => $this->clean($props['MillName'] ?? null),
            'latitude'            => $this->latitudeFromProperty($props, 'Latitude'),
            'longitude'           => $this->longitudeFromProperty($props, 'Longitude'),
            'physical_address'    => $this->clean($props['AddressPhysical'] ?? null),
            'physical_city'       => $this->clean($props['City'] ?? null),
            'physical_state'      => $this->stateCode($props['State'] ?? 'GA'),
            'physical_zip'        => $this->zip($props['Zip'] ?? null),
            'mailing_address'     => $this->clean($props['AddressMail'] ?? null),
            'mailing_city'        => $this->clean($props['City_1'] ?? null),
            'mailing_state'       => $this->stateCode($props['State_1'] ?? null),
            'mailing_zip'         => $this->zip($props['Zip_1'] ?? null),
            'county_name'         => $this->clean($props['County'] ?? null),
            'telephone'           => $this->clean($props['Telephone'] ?? null),
            'fax'                 => $this->clean($props['Fax'] ?? null),
            'email'               => $this->clean($props['Email'] ?? null),
            'web_site'            => $this->clean($props['Web'] ?? null),
            'contact_name'        => $this->clean($props['CEO'] ?? null),
            'size'                => $this->clean($props['Mill_Size'] ?? null),
            'type'                => $this->mapTypes($props),
            'species'             => $this->mapSpecies($props),
            'modification_date'   => $this->fromUnixMs($props['EditDate'] ?? null),
            'extended_attributes' => ! empty($extended) ? $extended : null,
        ], fn ($v) => $v !== null);
    }

    // -------------------------------------------------------------------------

    private function mapTypes(array $props): ?string
    {
        $types = [];

        foreach (['Product1', 'Product2', 'Product3'] as $field) {
            $raw = $this->clean($props[$field] ?? null);

            if ($raw === null) {
                continue;
            }

            $types = array_merge($types, $this->crosswalkType($raw));
        }

        return $this->pipeJoin($types);
    }

    /**
     * Returns one or more canonical type strings for a single GA product value.
     * Some values produce two types (e.g. Chip/Log Exports → Chip Mill + Log Export).
     *
     * @return array<int, string>
     */
    private function crosswalkType(string $raw): array
    {
        $crosswalk = [
            'Sawmill - Hardwood'            => ['Sawmill'],
            'Sawmill - Softwood'            => ['Sawmill'],
            'Sawmill - Hardwood & Softwood' => ['Sawmill'],
            'Chip Mill'                     => ['Chip Mill'],
            'Pulp & Paper'                  => ['Pulp & Paper'],
            'Pole - Post'                   => ['Post & Pole'],
            'Panel & Engineered Wood'       => ['Veneer / Plywood / Panels'],
            'OSB'                           => ['OSB'],
            'Energy Product'                => ['Energy Product'],
            'Energy Product-wood pellets'   => ['Pellet'],
            'Chip/Log Exports'              => ['Chip Mill', 'Log Export'],
            'Pole & Log Export'             => ['Post & Pole', 'Log Export'],
            'Firewood'                      => ['Firewood'],
            'Log Home'                      => ['Log Home'],
            'Mulch'                         => ['Mulch'],
            'Other'                         => ['Other'],
        ];

        return $crosswalk[$raw] ?? [];
    }

    /**
     * Merges species signals from the explicit Species field (HW/SW codes)
     * and from species-encoded Product field values ("Sawmill - Hardwood").
     */
    private function mapSpecies(array $props): ?string
    {
        $species = [];

        $rawSpecies = $this->clean($props['Species'] ?? null);

        if ($rawSpecies !== null) {
            $speciesMap = [
                'HW'      => ['Hardwood'],
                'SW'      => ['Softwood'],
                'HW & SW' => ['Hardwood', 'Softwood'],
            ];
            $species = array_merge($species, $speciesMap[$rawSpecies] ?? []);
        }

        foreach (['Product1', 'Product2', 'Product3'] as $field) {
            $raw = $this->clean($props[$field] ?? null);

            if ($raw === null) {
                continue;
            }

            if (str_contains($raw, 'Hardwood')) {
                $species[] = 'Hardwood';
            }

            if (str_contains($raw, 'Softwood')) {
                $species[] = 'Softwood';
            }
        }

        return $this->pipeJoin($species);
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        if ($v = $this->clean($props['SalesExecutive'] ?? null)) {
            $extended['sales_executive'] = $v;
        }

        if ($v = $this->clean($props['Mill_Type'] ?? null)) {
            $extended['mill_type_raw'] = $v;
        }

        if ($v = $this->clean($props['ProductNote'] ?? null)) {
            $extended['product_note'] = $v;
        }

        if ($v = $this->clean($props['By_Product'] ?? null)) {
            $extended['by_products'] = array_values(array_filter(
                array_map('trim', explode(',', $v)),
            ));
        }

        return $extended;
    }
}
