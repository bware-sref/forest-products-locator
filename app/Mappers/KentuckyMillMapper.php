<?php

namespace App\Mappers;

/**
 * Maps ArcGIS GeoJSON features from Kentucky's mill layer.
 *
 * Source: TBD — ArcGIS URL not yet obtained
 * Records: 303 in historical data (largest state dataset by record count)
 *
 * @todo Obtain KY ArcGIS FeatureServer URL and update config/arcgis.php
 * @todo Inspect source data and implement map() once a data file is available
 */
class KentuckyMillMapper extends AbstractMillMapper
{
    public function stateAbbreviation(): string
    {
        return 'KY';
    }

    public function map(array $feature): array
    {
        throw new \LogicException(
            'KentuckyMillMapper::map() is not yet implemented. '
            . 'Obtain the KY ArcGIS data file, inspect its schema, and implement this method.'
        );
    }
}
