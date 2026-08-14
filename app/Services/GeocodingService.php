<?php

namespace App\Services;

use App\Enums\AwsLocationIntendedUse;
use App\Enums\Environment;
use Aws\Laravel\AwsFacade as AWS;
use Aws\GeoPlaces\GeoPlacesClient;
use Illuminate\Support\Str;

class GeocodingService
{
    /**
     * If max and min have the same absolute value, do we need both?
     */
    public const float X_MAX = 180;
    public const float X_MIN = -180;
    public const float Y_MAX = 90;
    public const float Y_MIN = -90;

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

    /**
     * Is it even useful to allow specifying a biasPosition?
     * Certainly.
     * Does it complicate things?
     * Absolutely.
     * Should we allow omitting biasPosition altogether, or just changing it?
     * Sure.
     * How?
     * Pass false to omit or a valid value to override; otherwise you get the default value from configs.
     * Basically, anything except false or a valid substitute value means use the defaults.
     * What about null?
     * Defaults.
     * 
     * @param string $queryText
     * @param int $maxResults
     * @param string|AwsLocationIntendedUse $intendedUse
     * @param array|bool $biasPosition
     * @return array{city: mixed, country: mixed, country_code: mixed, county: mixed, label: mixed, latitude: mixed, longitude: mixed, state: mixed, state_code: mixed, street: mixed, street_address: string, street_number: mixed, zip: mixed[]|null}
     */
    public function geocode(
        string $queryText,
        int $maxResults = 1,
        string|AwsLocationIntendedUse $intendedUse = AwsLocationIntendedUse::Default,
        array|bool $biasPosition = [],
    ): ?array
    {
        /**
         * ensure $intendedUse is a valid value
         * If we use an enum instead, we don't really need the resolveIntendedUse method anymore.
         * Unless we also allow strings...:face-palm:
         * Even if we don't allow strings, it's still helpful for coercing values based on environment.
         * And it lets us wrap referencing the enum value property.
         */
        $intendedUse = static::resolveIntendedUse($intendedUse);

        $params = [
            'QueryText' => $queryText,
            'MaxResults' => $maxResults,
            'IntendedUse' => $intendedUse,
        ];

        /**
         * @var bool|array
         */
        $biasPosition = self::resolveBiasPosition($biasPosition);
        if (false !== $biasPosition) {
            $params['BiasPosition'] = $biasPosition;
        }

        $results = $this->client->geocode($params);

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
                // make it easier-ish by doing all this mess here
                'street_address' => Str::trim((($item['Address']['AddressNumber'] ?? '') . ' ' . ($item['Address']['Street'] ?? ''))),
                /**
                 * I'm omitting SecondaryAddressComponents for now because I don't think we need it.
                 * It's a 2D array, with inner keys 'Number' and 'Designator' in the example I made up.
                 * In any case, it seems messy to deal with unless we need it.
                 */
                // 'address_line2' => $item['Address']['SecondaryAddressComponents']
            ];
        }, $results['ResultItems']);
    }

    public function reverse(
        int|float|string $longitude,
        int|float|string $latitude,
        int $maxResults = 1,
        string|AwsLocationIntendedUse $intendedUse = AwsLocationIntendedUse::Default,
    ): ?array
    {
        /**
         * ensure $intendedUse is a valid value
         */
        $intendedUse = static::resolveIntendedUse($intendedUse);
        
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
            'IntendedUse' => $intendedUse,
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
                /**
                 * Wrap null coalescing in parentheses to make sure every possibly missing array key is accounted for.
                 */
                'street_address' => Str::trim((($item['Address']['AddressNumber'] ?? '') . ' ' . ($item['Address']['Street'] ?? ''))),
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

    /**
     * Resolves string or enum value and returns the string backing the enum's corresponding value.
     * If the value passed is invalid, returns the default intended use is returned.
     * @param string|AwsLocationIntendedUse $value
     * @return string
     */
    protected static function resolveIntendedUse(string|AwsLocationIntendedUse $value): string
    {
        /**
         * If we're not in production, always return SingleUse.
         * I.e., in local, dev, and testing environments.
         */
        if (Environment::Production->value !== config('app.env')) {
            return AwsLocationIntendedUse::SingleUse->value;
        }

        /**
         * If we're in production, try to find the appropriate value's value.
         * Default to SingleUse if $value isn't found.
         */
        return AwsLocationIntendedUse::tryFrom($value)?->value ?? AwsLocationIntendedUse::Default->value;        
    }

    /**
     * Reads the default bias position from configs, casts them to floats, and returns an [x, y] coordinate array.
     * Remember kids, x is longitude, y is latitude.
     * @return float[]
     */
    public static function defaultBiasPosition(): array
    {
        /**
         * Remember, X, Y!
         * And should we cast to float here or what?
         * We could also use floatval() with array_map(), but that seems like extra effort since
         * we aren't starting with an array.
         */
        return [
            (float) config('geocoding.bias_position.x'),
            (float) config('geocoding.bias_position.y')
        ];
    }

    /**
     * Resolves BiasPosition values to either false or a valid, 2-member array of floats.
     * A return value of false indicates BiasPosition should not be included in the location API request.
     *
     * @param bool|array $bp
     * @return bool|float[]
     */
    public static function resolveBiasPosition(bool|array $bp): array|bool
    {
        /**
         * False means false, bro!
         */
        if (false === $bp) {
            return false;
        }

        /**
         * If it's empty?
         * Defaults!
         * If it's not an array then it can only be true if we made it this far.
         * Defaults!
         * If it's an array with too few (i.e., fewer than 2 ;-) items?
         * Defaults!
         * We can chain the is_numeric() checks before slicing.
         * Except...we don't know yet if we have $bp[0] and $bp[1].
         * And I just verified with Tinker that is_numeric() on undefined causes a warning.
         * Null-coalesce to the rescue! (also verified with Tinker)
         * Except, we really don't care how the array is initially keyed, as long as it's ordered correctly.
         * Therefore, move the is_numeric() back to after slice because slice will reset the keys.
         * Well, no.
         * array_slice() only resets numeric keys, so we should do array_values() on the sliced result.
         * Back to the other conditional block with ye!
         */
        if (
            empty($bp) ||
            ! \is_array($bp) ||
            2 > \count($bp)
        ) {
            return static::defaultBiasPosition();
        }

        /**
         * Now that we're sure it's an array with at least 2 elements, slice it just to ensure it only has two elements.
         * Also do array_values() to ensure we have the keys 0 & 1 because array_slice() only resets numeric keys.
         */
        $bp = \array_slice(array_values($bp), 0, 2, false);

        /**
         * They're both spozta be numbers.
         * $bp[0] is spozta be X (longitude) so it must be between -180 and 180
         * $bp[1] is spozta be Y (latitude) so it must be between -90 and 90
         */
        if (! is_numeric($bp[0]) || ! is_numeric($bp[1]) ||
            static::X_MIN > $bp[0] || static::X_MAX < $bp[0] ||
            static::Y_MIN > $bp[1] || static::Y_MAX < $bp[1]
        ) {
            return static::defaultBiasPosition();
        }

        /**
         * If we made it this far, cast the values that were passed to float and return them.
         */
        return array_map('floatval', $bp);
    }
}
