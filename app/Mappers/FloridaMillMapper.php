<?php

namespace App\Mappers;

/**
 * Maps features from Florida's mill data.
 *
 * Source: State-supplied CSV → XLSX (no ArcGIS site known)
 * Records: 58 (replaces 59 historical FL records)
 *
 * FL is a file-based source, but since we store the GeoJSON representation
 * in mill_raw_imports.geojson (produced by the ArcGIS reader or a file
 * reader that normalises to the same structure), the mapper receives the
 * same array shape regardless of source.
 *
 * Notable FL quirks handled here:
 * - Phone and email fields may contain two values separated by ' / '
 * - Physical address may be the string "SAME" (meaning = mailing address)
 * - _x000D_ carriage-return artifacts in phone/fax fields (handled by clean())
 * - Mailing and physical addresses are combined strings → pass to geocoder
 */
class FloridaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'FL';
    }

    public function map(array $feature): array
    {
        $props = $feature['properties'] ?? [];

        [$telephone, $telephone2] = $this->splitContactField($props['Contact Phone #'] ?? null);
        [$email, $email2]         = $this->splitContactField($props['Contact Email Address'] ?? null);

        $physical = $this->resolvePhysicalAddress($props);
        $mailing  = $this->clean($props['Mailing Address'] ?? null);

        $extended = $this->buildExtendedAttributes($props);

        return array_filter([
            'mill_name'           => $this->clean($props['Company Name'] ?? null),
            'latitude'            => $this->latitudeFromProperty($props, 'Latitude dec deg'),
            'longitude'           => $this->longitudeFromProperty($props, 'Longitude dec deg'),
            'county_name'         => $this->clean($props['County'] ?? null),
            'physical_address'    => $physical,
            'physical_state'      => 'FL',
            'mailing_address'     => $mailing,
            'telephone'           => $telephone,
            'telephone_2'         => $telephone2,
            'fax'                 => $this->clean($props['Fax #'] ?? null),
            'email'               => $email,
            'email_2'             => $email2,
            'web_site'            => $this->clean($props['Website'] ?? null),
            'contact_name'        => $this->clean($props['Contact Name:'] ?? null),
            'size'                => $this->clean($props['Mill Size'] ?? null),
            'type'                => $this->mapType($props['Mill Type'] ?? null),
            'species'             => $this->mapSpecies($props['Species'] ?? null),
            'extended_attributes' => ! empty($extended) ? $extended : null,
        ], fn ($v) => $v !== null);
    }

    // -------------------------------------------------------------------------

    /**
     * Split a contact field on ' / ' into [primary, secondary|null].
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function splitContactField(?string $raw): array
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return [null, null];
        }

        if (str_contains($cleaned, '/')) {
            $parts = array_map('trim', explode('/', $cleaned, 2));

            return [
                $this->clean($parts[0]) ?: null,
                $this->clean($parts[1]) ?: null,
            ];
        }

        return [$cleaned, null];
    }

    /**
     * Returns the physical address string.
     * If the value is "SAME", returns the mailing address instead.
     */
    private function resolvePhysicalAddress(array $props): ?string
    {
        $physical = $this->clean($props['Physical Address'] ?? null);

        if ($physical !== null && strtoupper($physical) === 'SAME') {
            return $this->clean($props['Mailing Address'] ?? null);
        }

        return $physical;
    }

    private function mapType(?string $raw): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        $crosswalk = [
            'Sawmill'               => 'Sawmill',
            'Chip-n-saw'            => 'Chip-n-Saw',
            'Chip'                  => 'Chip Mill',
            'Pulp & Paper'          => 'Pulp & Paper',
            'Post'                  => 'Post & Pole',
            'Pole'                  => 'Post & Pole',
            'Veneer/Panel'          => 'Veneer / Plywood / Panels',
            'Oriented Strand Board' => 'OSB',
            'Pellet'                => 'Pellet',
            'Biomass Power Plant'   => 'Biomass Power Plant',
            'Firewood'              => 'Firewood',
            'Animal Bedding'        => 'Animal Bedding',
            'Shavings'              => 'Wood Shavings',
            'Mulch'                 => 'Mulch',
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

        // Normalise case variations ("softwood", "Softwood Cypress", etc.)
        $lower = strtolower($cleaned);

        // Use inferSpecies for FL's varied free-text species values
        $remainder = null;
        $resolved  = $this->inferSpecies($cleaned, $remainder);

        return $this->pipeJoin($resolved);
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        if ($v = $this->clean($props['FIA Unit'] ?? null)) {
            $extended['fia_unit'] = $v;
        }

        if ($v = $this->clean($props['Status'] ?? null)) {
            $extended['status'] = $v;
        }

        return $extended;
    }
}
