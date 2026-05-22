<?php

namespace App\Helpers;

class Geo
{

    public const LAT_DEG_TO_MILES = 69.172; // miles = 1 degree of latitude
    public const LAT_DEG_TO_KM = 111.32; // km = 1 degree of latitude

    private static function getUnit(string $unit): float
    {
        return ($unit === 'km') ? self::LAT_DEG_TO_KM : self::LAT_DEG_TO_MILES;
    }

    /**
     * Given a latitude in degrees, returns the distance (in miles or km) spanned by 1 degree of longitude.
     */
    public static function longitudeDistanceAtLatitude(float $latitude, ?string $unit = 'miles'): float
    {
        $d = self::getUnit($unit) * cos(deg2rad($latitude));
        return $d;
    }

    /**
     * Given a distance in miles (or km) at a given latitude, returns the degrees of longitude spanned by that distance at that latitude.
     */
    public static function distanceToDegreesLongitude(float $d, ?float $latitude = 0, ?string $unit = 'miles'): float
    {
        return $d / self::longitudeDistanceAtLatitude($latitude, $unit);
    }

    /**
     * Given a distance in miles (or km), returns the number of degrees latitude spanned by that distance.
     */
    public static function distanceToDegreesLatitude(float $d, ?string $unit = 'miles'): float
    {        
        return $d / self::getUnit($unit);
    }
}
