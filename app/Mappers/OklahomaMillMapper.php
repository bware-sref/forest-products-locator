<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Oklahoma's mill layer.
 *
 * Source: ArcGIS FeatureServer API extract
 * Records: 70 total; 3 non-OK records excluded → 67 records.
 *          Non-OK records are filtered in ProcessArcGisImport before
 *          the mapper is called.
 *
 * OK is the most consumer-facing schema seen — operational capability
 * fields (Will_it_*, Can_*), maximum travel radius, minimum log specs.
 *
 * Mill type cannot be reliably determined from Products_Produced (free-text).
 * Per audit decision: do not attempt type inference; leave type null.
 *
 * Species_Type is partially mappable (Hardwood, Softwood, All, Pine, etc.)
 * but frequently contains free-text notes mixed with species names.
 * Clearly discernible values → wood_species pivot.
 * Ambiguous/mixed values → extended_attributes.species_type.
 */
class OklahomaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'OK';
    }

    /**
     * Known OK capability field names.
     * All map to extended_attributes.capabilities as a JSON object.
     */
    private const CAPABILITY_FIELDS = [
        'Will_it_Buy_Timber',
        'Can_Timber_be_Brought_to_Mill',
        'Will_it_Cut_Standing_Timber',
        'Will_it_Pick_up_Cut_Timber',
        'Will_it_Accept_Single_Small_Gro',
        'Will_it_Purchase_Single_Small_G',
    ];

    /**
     * OK: Exclude the 3 records where State is Arkansas or Texas.
     */
    public function shouldImport(array $feature): bool
    {
        $state = strtolower(trim($feature['properties']['State'] ?? ''));

        return in_array($state, ['ok', 'oklahoma'], strict: true);
    }

    public function map(array $feature): array
    {
        $props    = $feature['properties'] ?? [];
        $extended = $this->buildExtendedAttributes($props);

        return array_filter([
            'mill_name'           => $this->stripTrailingAsterisks($props['Company_Name'] ?? null),
            'latitude'            => $this->latitude($feature),
            'longitude'           => $this->longitude($feature),
            'physical_address'    => $this->clean($props['Physical_Address'] ?? null),
            'physical_city'       => $this->clean($props['City'] ?? null),
            'physical_state'      => $this->stateCode($props['State'] ?? 'OK'),
            'physical_zip'        => $this->zip($props['Zip'] ?? null),
            'mailing_address'     => $this->clean($props['Mailing_Address'] ?? null),
            'county_name'         => $this->clean($props['County'] ?? null),
            'telephone'           => $this->clean($props['Telephone_Number'] ?? null),
            'email'               => $this->clean($props['Email_Address'] ?? null),
            'web_site'            => $this->clean($props['Website'] ?? null),
            'contact_name'        => $this->clean($props['Contact'] ?? null),
            // type intentionally omitted — Products_Produced is free-text
            'species'             => $this->mapSpecies($props['Species_Type'] ?? null, $extended),
            'modification_date'   => $this->fromUnixMs($props['EditDate'] ?? null),
            'extended_attributes' => ! empty($extended) ? $extended : null,
        ], fn ($v) => $v !== null);
    }

    // -------------------------------------------------------------------------

    /**
     * Map Species_Type to canonical wood_species where clearly discernible.
     * Populates $extended['species_type'] with the raw value when ambiguous.
     * $extended is passed by reference so we can add to it here.
     */
    private function mapSpecies(?string $raw, array &$extended): ?string
    {
        $cleaned = $this->clean($raw);

        if ($cleaned === null) {
            return null;
        }

        $remainder = null;
        $resolved  = $this->inferSpecies($cleaned, $remainder);

        if ($remainder !== null) {
            $extended['species_type'] = $remainder;
        }

        return $this->pipeJoin($resolved);
    }

    private function buildExtendedAttributes(array $props): array
    {
        $extended = [];

        if ($v = $this->clean($props['Specialty_Species'] ?? null)) {
            $extended['species_detail'] = $v;
        }

        if ($v = $this->clean($props['Products_Produced'] ?? null)) {
            $extended['product_note'] = $v;
        }

        if ($v = $this->clean($props['Status'] ?? null)) {
            $extended['status'] = $v;
        }

        if ($v = $props['Maximum_Travel'] ?? null) {
            if (is_numeric($v)) {
                $extended['maximum_travel_miles'] = (int) $v;
            }
        }

        if ($v = $this->clean($props['Minimum_Specs'] ?? null)) {
            $extended['minimum_log_specs'] = $v;
        }

        // Collect all capability Yes/No fields into a single JSON object
        $capabilities = [];
        foreach (self::CAPABILITY_FIELDS as $field) {
            $val = $this->clean($props[$field] ?? null);
            if ($val !== null) {
                $capabilities[$field] = strtolower($val) === 'yes';
            }
        }
        if (! empty($capabilities)) {
            $extended['capabilities'] = $capabilities;
        }

        return $extended;
    }
}
