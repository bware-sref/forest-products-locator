<?php

namespace App\Services;

use Aws\Laravel\AwsFacade as AWS;
use Aws\GeoPlaces\GeoPlacesClient;

class GeocodingService
{
    protected GeoPlacesClient $client;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        /**
         * @var GeoPlacesClient
         */
        $this->client = AWS::createClient('geoPlaces');
    }

    public function geocode(string $queryText, int $maxResults = 1): ?array
    {
        $results = $this->client->geocode([
            'QueryText' => $queryText,
            'MaxResults' => $maxResults,
        ]);

        if (empty($results)) {
            return null;
        }

        return array_map(function ($item) {
            /**
             * Jaha!
             * Claude's notion of this structure is out of date.
             * There is not a 'Place' key.
             * Instead, 'Address' is top level in each result.
             */
            return [
                // label is the full, normalized address
                'label' => $item['Address']['Label'] ?? null,
                'longitude' => $item['Position'][0] ?? null,
                'latitude' => $item['Position'][1] ?? null,
                'country' => $item['Address']['Country']['Name'] ?? null,
                // e.g., US
                'country_code' => $item['Address']['Country']['Code2'] ?? null,
                // state
                'state' => $item['Address']['Region']['Name'] ?? null,
                'state_code' => $item['Address']['Region']['Code'] ?? null,
                'county' => $item['Address']['SubRegion']['Name'] ?? null,
                'city' => $item['Address']['Locality'] ?? null,
                'zip' => $item['Address']['PostalCode'] ?? null,
                'street' => $item['Address']['Street'] ?? null,
                'street_number' => $item['Address']['AddressNumber'] ?? null,
                /**
                 * I'm omitting SecondaryAddressComponents for now because I don't think we need it.
                 * It's a 2D array, with inner keys 'Number' and 'Designator' in the example I made up.
                 * In any case, it seems messy to deal with unless we need it.
                 */
                // 'address_line2' => $item['Address']['SecondaryAddressComponents']
            ];
        }, $results['ResultItems']);
    }

    public function reverse(int|float|string $longitude, int|float|string $latitude, int $maxResults = 1): ?array
    {
        /**
         * Hmm...I might rather handle casting longitude and latitude to floats here instead of requiring them to be passed that way
         */
        $results = $this->client->reverseGeocode([
            'QueryPosition' => [
                /**
                 * remember: x,y
                 * also, let's cast to float here so we don't ahve to do it everywhere
                 */
                (float) $longitude,
                (float) $latitude,
            ],
            'MaxResults' => $maxResults,
        ]);

        if (empty($results)) {
            return null;
        }

        return array_map(function ($item) {
            return [
                // label is the full, normalized address
                'label' => $item['Address']['Label'] ?? null,
                'longitude' => $item['Position'][0] ?? null,
                'latitude' => $item['Position'][1] ?? null,
                'country' => $item['Address']['Country']['Name'] ?? null,
                // e.g., US
                'country_code' => $item['Address']['Country']['Code2'] ?? null,
                // state
                'state' => $item['Address']['Region']['Name'] ?? null,
                'state_code' => $item['Address']['Region']['Code'] ?? null,
                'county' => $item['Address']['SubRegion']['Name'] ?? null,
                'city' => $item['Address']['Locality'] ?? null,
                'zip' => $item['Address']['PostalCode'] ?? null,
                'street' => $item['Address']['Street'] ?? null,
                'street_number' => $item['Address']['AddressNumber'] ?? null,
            ];
        }, $results['ResultItems']);
    }

    /**
     * GeoPlacesClient has the following additional methods which we can add if we end up needing them.
     * However, as you might guess, they all accept an array as their only argument, so we'll have to refer to AWS Location API
     * (or Claude) to see what data needs to be in each methods array.
     * Since we don't if we need them, skipping them for now.
     * 
     * autocomplete
     * getPlace
     * searchNearby
     * searchText
     * suggest
     */
}
