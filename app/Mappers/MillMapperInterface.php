<?php

namespace App\Mappers;

/**
 * Contract for all state-specific mill mappers.
 *
 * A mapper's sole responsibility is to translate a raw GeoJSON feature
 * (as decoded from mill_raw_imports.geojson) into a flat array of mills
 * column values ready for insertion or update.
 *
 * The mapper does NOT:
 *  - Write to the database
 *  - Resolve foreign keys (county_id, state_id, etc.)
 *  - Geocode addresses
 *  - Populate pivot tables
 *
 * Those concerns are handled by the existing job chain
 * (GeocodeMill → ProcessMillState → ProcessMillMillTypes →
 *  ProcessMillWoodSpecies → UpdateImportProcessedRows).
 *
 * Multi-value fields (type, species) are returned as pipe-delimited
 * strings, which is the format expected by ProcessMillMillTypes and
 * ProcessMillWoodSpecies.
 */
interface MillMapperInterface
{
    /**
     * Map a decoded GeoJSON feature to a mills column array.
     *
     * @param  array<string, mixed>  $feature  Decoded GeoJSON feature:
     *                                         ['type' => 'Feature',
     *                                          'id' => ...,
     *                                          'geometry' => [...],
     *                                          'properties' => [...]]
     * @return array<string, mixed>  Column => value pairs for the mills table.
     *                               Keys must match mills column names exactly.
     *                               Omitted keys are not written (not set to null).
     */
    public function map(array $feature): array;

    /**
     * Determine whether a feature should be imported at all.
     *
     * Return false to skip the feature entirely — no mill_raw_imports row
     * is created and no job is dispatched for it.
     *
     * The default implementation in AbstractMillMapper returns true, so
     * concrete mappers only need to override this when their state's data
     * requires filtering (e.g. AL Primary-only, OK non-state records).
     */
    public function shouldImport(array $feature): bool;

    /**
     * The two-letter state abbreviation this mapper handles.
     * Used to hard-code mills.physical_state where source data omits it.
     */
    public function stateAbbreviation(): string;
}
