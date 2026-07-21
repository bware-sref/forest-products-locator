<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Arkansas's mill layer.
 *
 * Source: TBD — ArcGIS URL not yet obtained
 * Records: 84 in historical data
 *
 * @todo Obtain AR ArcGIS FeatureServer URL and update config/arcgis.php
 * @todo Inspect source data and implement map() once a data file is available
 */
class ArkansasMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'AR';
    }

    public function map(array $feature): array
    {
        throw new \LogicException(
            'ArkansasMillMapper::map() is not yet implemented. '
            . 'Obtain the AR ArcGIS data file, inspect its schema, and implement this method.'
        );
    }
}
