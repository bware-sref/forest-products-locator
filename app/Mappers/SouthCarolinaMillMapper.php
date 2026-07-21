<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from South Carolina's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract (CSV)
 * Records: 91 (replaces 95 historical SC records)
 *
 * SC uses abbreviated Type codes (SawS, SawM, SawL, etc.) that encode
 * both the canonical mill type and, for sawmills, a size qualifier.
 *
 * Location = physical address; Address = mailing address.
 * Coordinates are WGS84 from the X/Y properties.
 *
 * The 'Portable' type code produces two pivot rows: Sawmill + Portable Sawmill.
 * The 'ShavMulch' type code produces two pivot rows: Wood Shavings + Mulch.
 */
class SouthCarolinaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'SC';
    }

    public function map(array $feature): array
    {
        $props = $feature['properties'] ?? [];

        [$type, $size] = $this->mapType($props['Type'] ?? null);

        $extended = $this->buildExtendedAttributes($props);

        return array_filter([
            'mill_name'           => $this->clean($props['Name'] ?? null),
            'latitude'            => $this->latitudeFromProperty($props, 'Y'),
            'longitude'           => $this->longitudeFromProperty($props, 'X'),
            'physical_address'    => $this->clean($props['Location'] ?? null),
            'physical_state'      => 'SC',
            'mailing_address'     => $this->clean($props['Address'] ?? null),
            'county_name'         => $this->clean($props['County'] ?? null),
            'telephone'           => $this->clean($props['Phone'] ?? null),
            'fax'                 => $this->clean($props['Fax'] ?? null),
            'email'               => $this->clean($props['Email'] ?? null),
            'web_site'            => $this->clean($props['Web'] ?? null),
            'contact_name'        => $this->buildContactName($props),
            'contact_title'       => $this->clean($props['Title'] ?? null),
            'type'                => $type,
            'species'             => $this->mapSpecies($props['Species'] ?? null),
            'size'                => $size,
            'extended_attributes' => ! empty($extended) ? $extended : null,
        ], fn ($v) => $v !== null);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns [type_string|null, size_string|null].
     * Sawmill codes carry a size qualifier; Portable produces two types.
     */
    private function mapType(?string $raw): array
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return [null, null];
        }

        $crosswalk = [
            'SawS'      => [['Sawmill'],                        'Small'],
            'SawM'      => [['Sawmill'],                        'Medium'],
            'SawL'      => [['Sawmill'],                        'Large'],
            'Portable'  => [['Sawmill', 'Portable Sawmill'],    null],
            'ShavMulch' => [['Wood Shavings', 'Mulch'],         null],
            'PP'        => [['Post & Pole'],                    null],
            'VP'        => [['Veneer / Plywood / Panels'],      null],
            'LEY'       => [['Log Export'],                     null],
            'BE'        => [['Energy Product'],                 null],
            'PulpPaper' => [['Pulp & Paper'],                   null],
            'ChipMill'  => [['Chip Mill'],                      null],
            'PSM'       => [['Pallet'],                         null],
        ];

        if (! isset($crosswalk[$cleaned])) {
            return [null, null];
        }

        [$types, $size] = $crosswalk[$cleaned];

        return [$this->pipeJoin($types), $size];
    }

    private function mapSpecies(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        // Strip parenthetical specifics e.g. "Hardwood (& Cypress)"
        $stripped = preg_replace('/\s*\(.*?\)/', '', $cleaned);
        $stripped = trim($stripped ?? $cleaned);

        $species = [];

        foreach (explode('&', $stripped) as $token) {
            $token = trim($token);

            match (strtolower($token)) {
                'hardwood' => $species[] = 'Hardwood',
                'softwood' => $species[] = 'Softwood',
                default    => null, // specific species go to extended_attributes.species_detail
            };
        }

        return $this->pipeJoin($species);
    }

    private function buildContactName(array $props): ?string
    {
        $first = $this->clean($props['First_Name'] ?? null);
        $last  = $this->clean($props['Last_Name'] ?? null);

        $parts = array_filter([$first, $last]);

        return ! empty($parts) ? implode(' ', $parts) : null;
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        // Species2 contains specific species names (e.g. "Pine", "Red oak")
        if ($v = $this->clean($props['Species2'] ?? null)) {
            $extended['species_detail'] = $v;
        }

        return $extended;
    }
}
