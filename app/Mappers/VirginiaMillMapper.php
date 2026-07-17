<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Virginia's mill layer.
 *
 * Source: TBD — ArcGIS URL not yet obtained
 * Records: 165 in historical data (plus 5 with state value "Virginia"
 *          rather than "VA" — normalised during historical data import)
 *
 * @todo Obtain VA ArcGIS FeatureServer URL and update config/arcgis.php
 * @todo Inspect source data and implement map() once a data file is available
 */
class VirginiaMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'VA';
    }

    public function map(array $feature): array
    {
        throw new \LogicException(
            'VirginiaMillMapper::map() is not yet implemented. '
            . 'Obtain the VA ArcGIS data file, inspect its schema, and implement this method.'
        );
    }
}
