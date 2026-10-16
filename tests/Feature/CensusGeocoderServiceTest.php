<?php

use App\Services\CensusGeocoderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

// Multipart requests carry their non-file fields as a list of
// ['name' => ..., 'contents' => ...] parts rather than URL query params or
// a form body, so assertSent() callbacks read this instead of parse_str().
function multipartField(Request $request, string $name): ?string
{
    return collect($request->data())->firstWhere('name', $name)['contents'] ?? null;
}

// A real `locations/onelineaddress` response (benchmark 4, format=json), captured 2026-10-16 for
// "4600 Silver Hill Rd, Washington, DC 20233". Keep it real-shaped: formatGeocodeResult() reads
// addressComponents, and a trimmed-down fake silently produces empty city/state/zip.
function censusOneLineAddressResponse(): array
{
    return [
        'result' => [
            'input' => [
                'address' => ['address' => '4600 Silver Hill Rd, Washington, DC 20233'],
                'benchmark' => [
                    'isDefault' => true,
                    'benchmarkDescription' => 'Public Address Ranges - Current Benchmark',
                    'id' => '4',
                    'benchmarkName' => 'Public_AR_Current',
                ],
            ],
            'addressMatches' => [
                [
                    'tigerLine' => ['side' => 'L', 'tigerLineId' => '657091557'],
                    'coordinates' => ['x' => -76.92836638093, 'y' => 38.84505589808],
                    'addressComponents' => [
                        'zip' => '20233',
                        'streetName' => 'SILVER HILL',
                        'preType' => '',
                        'city' => 'WASHINGTON',
                        'preDirection' => '',
                        'suffixDirection' => '',
                        'fromAddress' => '4600',
                        'state' => 'DC',
                        'suffixType' => 'RD',
                        'toAddress' => '4700',
                        'suffixQualifier' => '',
                        'preQualifier' => '',
                    ],
                    'matchedAddress' => '4600 SILVER HILL RD, WASHINGTON, DC, 20233',
                ],
            ],
        ],
    ];
}

beforeEach(function () {
    Config::set('census-geocoder', [
        'base_url' => 'https://geocoding.geo.census.gov/geocoder',
        'timeout' => 15,
        'benchmark' => '4',
        'vintage' => '4',
        'endpoints' => [
            'benchmarks' => 'benchmarks',
            'vintages' => 'vintages',
        ],
    ]);
});

// ---------------------------------------------------------------------------
// oneLineAddress()
// ---------------------------------------------------------------------------

describe('oneLineAddress()', function () {

    it('returns the formatted first match when addressMatches are found', function () {
        Http::fake(['*/locations/onelineaddress*' => Http::response(censusOneLineAddressResponse())]);

        $result = (new CensusGeocoderService())->oneLineAddress('4600 Silver Hill Rd, Washington, DC 20233');

        expect($result)->toBe([
            'query' => '4600 Silver Hill Rd, Washington, DC 20233',
            'latitude' => 38.84505589808,
            'longitude' => -76.92836638093,
            'state' => 'DC',
            'city' => 'Washington',
            'zip' => '20233',
            // city/state/zip are stripped from matchedAddress before title-casing, so the state abbreviation never reaches title()
            'street_address' => '4600 Silver Hill Rd',
            'addressComponents' => censusOneLineAddressResponse()['result']['addressMatches'][0]['addressComponents'],
        ]);
    });

    it('strips only the trailing city, state, and zip from street_address', function (
        string $matchedAddress,
        string $city,
        string $state,
        string $zip,
        string $expectedStreet,
    ) {
        $response = censusOneLineAddressResponse();
        $response['result']['addressMatches'][0]['matchedAddress'] = $matchedAddress;
        $response['result']['addressMatches'][0]['addressComponents'] = array_merge(
            $response['result']['addressMatches'][0]['addressComponents'],
            ['city' => $city, 'state' => $state, 'zip' => $zip],
        );
        Http::fake(['*/locations/onelineaddress*' => Http::response($response)]);

        $result = (new CensusGeocoderService())->oneLineAddress($matchedAddress);

        expect($result['street_address'])->toBe($expectedStreet);
    })->with([
        'city name in the street name' => ['123 WASHINGTON ST, WASHINGTON, DC, 20233', 'WASHINGTON', 'DC', '20233', '123 Washington St'],
        'state abbreviation inside a street word' => ['1 ALABAMA ST, MOBILE, AL, 36602', 'MOBILE', 'AL', '36602', '1 Alabama St'],
        'zip digits in the house number' => ['20233 SILVER HILL RD, WASHINGTON, DC, 20233', 'WASHINGTON', 'DC', '20233', '20233 Silver Hill Rd'],
    ]);

    it('returns the raw result when rawOutput is true', function () {
        Http::fake(['*/locations/onelineaddress*' => Http::response(censusOneLineAddressResponse())]);

        $result = (new CensusGeocoderService())->oneLineAddress('4600 Silver Hill Rd, Washington, DC 20233', rawOutput: true);

        expect($result)->toBe(censusOneLineAddressResponse()['result'])
            ->and($result['addressMatches'])->toHaveCount(1)
            ->and($result['addressMatches'][0]['matchedAddress'])->toBe('4600 SILVER HILL RD, WASHINGTON, DC, 20233');
    });

    it('sends the address, benchmark, and json format params', function () {
        Http::fake(['*/locations/onelineaddress*' => Http::response(['result' => ['addressMatches' => []]])]);

        (new CensusGeocoderService())->oneLineAddress('123 Main St');

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return $params['address'] === '123 Main St'
                && $params['benchmark'] === '4'
                && $params['format'] === 'json'
                && str_starts_with($request->url(), 'https://geocoding.geo.census.gov/geocoder/locations/onelineaddress');
        });
    });

    it('returns null when there are no addressMatches', function () {
        Http::fake([
            '*/locations/onelineaddress*' => Http::response([
                'result' => ['input' => [], 'addressMatches' => []],
            ]),
        ]);

        $result = (new CensusGeocoderService())->oneLineAddress('not a real address');

        expect($result)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// coordinates()
// ---------------------------------------------------------------------------

describe('coordinates()', function () {

    it('returns the decoded result when geographies are found', function () {
        Http::fake([
            '*/geographies/coordinates*' => Http::response([
                'result' => [
                    'input' => ['location' => ['x' => -76.92748, 'y' => 38.84601]],
                    'geographies' => [
                        'Counties' => [['NAME' => "Prince George's County"]],
                    ],
                ],
            ]),
        ]);

        $result = (new CensusGeocoderService())->coordinates(-76.92748, 38.84601);

        expect($result['geographies']['Counties'][0]['NAME'])->toBe("Prince George's County");
    });

    it('sends x, y, benchmark, vintage, and json format params', function () {
        Http::fake(['*/geographies/coordinates*' => Http::response(['result' => ['geographies' => []]])]);

        (new CensusGeocoderService())->coordinates('-76.92748', '38.84601');

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return $params['x'] === '-76.92748'
                && $params['y'] === '38.84601'
                && $params['benchmark'] === '4'
                && $params['vintage'] === '4'
                && $params['format'] === 'json'
                && ! isset($params['layers']);
        });
    });

    it('joins an array of layers into a comma-delimited param', function () {
        Http::fake(['*/geographies/coordinates*' => Http::response(['result' => ['geographies' => []]])]);

        (new CensusGeocoderService())->coordinates(-76.92748, 38.84601, layers: ['10', '86']);

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return $params['layers'] === '10,86';
        });
    });

    it('returns null when there are no geographies', function () {
        Http::fake([
            '*/geographies/coordinates*' => Http::response([
                'result' => ['input' => [], 'geographies' => []],
            ]),
        ]);

        $result = (new CensusGeocoderService())->coordinates(0, 0);

        expect($result)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// benchmark()/vintage() defaults
// ---------------------------------------------------------------------------

describe('benchmark/vintage defaults', function () {

    it('falls back to the class constants when config values are unset', function () {
        Config::set('census-geocoder.benchmark', null);
        Config::set('census-geocoder.vintage', null);

        Http::fake(['*/locations/onelineaddress*' => Http::response(['result' => ['addressMatches' => []]])]);

        (new CensusGeocoderService())->oneLineAddress('123 Main St');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'benchmark=4'));
    });

    it('uses a configured benchmark/vintage instead of the class constants', function () {
        Config::set('census-geocoder.benchmark', 'Public_AR_Census2020');
        Config::set('census-geocoder.vintage', 'Census2020_Census2020');

        Http::fake(['*/geographies/coordinates*' => Http::response(['result' => ['geographies' => []]])]);

        (new CensusGeocoderService())->coordinates(-76.92748, 38.84601);

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return $params['benchmark'] === 'Public_AR_Census2020'
                && $params['vintage'] === 'Census2020_Census2020';
        });
    });

    it('lets an explicit argument override the config default', function () {
        Config::set('census-geocoder.benchmark', 'Public_AR_Census2020');

        Http::fake(['*/locations/onelineaddress*' => Http::response(['result' => ['addressMatches' => []]])]);

        (new CensusGeocoderService())->oneLineAddress('123 Main St', benchmark: 'Public_AR_ACS2023');

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return $params['benchmark'] === 'Public_AR_ACS2023';
        });
    });

});

// ---------------------------------------------------------------------------
// addressBatch()
// ---------------------------------------------------------------------------

describe('addressBatch()', function () {

    it('posts a multipart CSV file to locations/addressbatch by default', function () {
        Http::fake([
            '*/locations/addressbatch*' => Http::response(
                "1,\"123 Main St, Anytown, ST, 00000\",Match,Exact,\"123 MAIN ST, ANYTOWN, ST, 00000\",\"-76.92,38.84\",12345,L\n"
            ),
        ]);

        $rows = (new CensusGeocoderService())->addressBatch([
            [1, '123 Main St', 'Anytown', 'ST', '00000'],
        ]);

        expect($rows)->toHaveCount(1)
            ->and($rows[0][0])->toBe('1')
            ->and($rows[0][2])->toBe('Match');

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/locations/addressbatch')
                && multipartField($request, 'benchmark') === '4'
                && $request->hasFile('addressFile', "1,\"123 Main St\",Anytown,ST,00000\n");
        });
    });

    it('adds vintage and posts to geographies/addressbatch when requested', function () {
        Http::fake(['*/geographies/addressbatch*' => Http::response('')]);

        (new CensusGeocoderService())->addressBatch(
            [[1, '123 Main St', 'Anytown', 'ST', '00000']],
            returnType: 'geographies',
        );

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/geographies/addressbatch')
                && multipartField($request, 'vintage') === '4';
        });
    });

    it('returns an empty array for an empty response body', function () {
        Http::fake(['*/locations/addressbatch*' => Http::response('')]);

        $rows = (new CensusGeocoderService())->addressBatch([[1, '123 Main St', 'Anytown', 'ST', '00000']]);

        expect($rows)->toBe([]);
    });

    it('throws when given more than MAX_BATCH_ROWS rows', function () {
        $rows = array_map(fn ($i) => [$i, "{$i} Main St", 'Anytown', 'ST', '00000'], range(1, CensusGeocoderService::MAX_BATCH_ROWS + 1));

        (new CensusGeocoderService())->addressBatch($rows);
    })->throws(InvalidArgumentException::class, 'limited to');

});

// ---------------------------------------------------------------------------
// coordinatesBatch()
// ---------------------------------------------------------------------------

describe('coordinatesBatch()', function () {

    it('posts a multipart CSV file to geographies/coordinatesbatch', function () {
        Http::fake([
            '*/geographies/coordinatesbatch*' => Http::response(
                "1,-76.92748,38.84601,\"Prince George's County\"\n"
            ),
        ]);

        $rows = (new CensusGeocoderService())->coordinatesBatch([
            [1, -76.92748, 38.84601],
        ]);

        expect($rows)->toHaveCount(1)
            ->and($rows[0][0])->toBe('1')
            ->and($rows[0][3])->toBe("Prince George's County");

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/geographies/coordinatesbatch')
                && multipartField($request, 'benchmark') === '4'
                && multipartField($request, 'vintage') === '4'
                && $request->hasFile('coordinatesFile', "1,-76.92748,38.84601\n");
        });
    });

});

// ---------------------------------------------------------------------------
// benchmarks()
// ---------------------------------------------------------------------------

describe('benchmarks()', function () {

    it('returns the benchmarks list', function () {
        Http::fake([
            '*/benchmarks*' => Http::response([
                'benchmarks' => [
                    ['isDefault' => true, 'benchmarkDescription' => 'Public Address Ranges - Current Benchmark', 'id' => '4', 'benchmarkName' => 'Public_AR_Current'],
                    ['isDefault' => false, 'benchmarkDescription' => 'Public Address Ranges - Census 2020 Benchmark', 'id' => '2020', 'benchmarkName' => 'Public_AR_Census2020'],
                ],
            ]),
        ]);

        $benchmarks = (new CensusGeocoderService())->benchmarks();

        expect($benchmarks)->toHaveCount(2)
            ->and($benchmarks[0]['benchmarkName'])->toBe('Public_AR_Current');

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return str_contains($request->url(), '/benchmarks')
                && $params['format'] === 'json';
        });
    });

    it('returns an empty array when the response has no benchmarks key', function () {
        Http::fake(['*/benchmarks*' => Http::response([])]);

        expect((new CensusGeocoderService())->benchmarks())->toBe([]);
    });

});

// ---------------------------------------------------------------------------
// vintages()
// ---------------------------------------------------------------------------

describe('vintages()', function () {

    it('returns the vintages list for the configured benchmark by default', function () {
        Http::fake([
            '*/vintages*' => Http::response([
                'vintages' => [
                    ['isDefault' => true, 'id' => '4', 'vintageName' => 'Current_Current', 'vintageDescription' => 'Current Vintage - Current Benchmark'],
                ],
                'selectedBenchmark' => '4',
                'benchmarks' => [],
            ]),
        ]);

        $vintages = (new CensusGeocoderService())->vintages();

        expect($vintages)->toHaveCount(1)
            ->and($vintages[0]['vintageName'])->toBe('Current_Current');

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return str_contains($request->url(), '/vintages')
                && $params['benchmark'] === '4';
        });
    });

    it('queries an explicit benchmark instead of the configured default', function () {
        Http::fake(['*/vintages*' => Http::response(['vintages' => []])]);

        (new CensusGeocoderService())->vintages('Public_AR_Census2020');

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            return $params['benchmark'] === 'Public_AR_Census2020';
        });
    });

    it('returns an empty array when the response has no vintages key', function () {
        Http::fake(['*/vintages*' => Http::response(['selectedBenchmark' => '4', 'benchmarks' => []])]);

        expect((new CensusGeocoderService())->vintages())->toBe([]);
    });

});

// ---------------------------------------------------------------------------
// error handling
// ---------------------------------------------------------------------------

describe('error handling', function () {

    it('throws on a non-2xx HTTP response', function () {
        Http::fake(['*/locations/onelineaddress*' => Http::response([], 500)]);

        (new CensusGeocoderService())->oneLineAddress('123 Main St');
    })->throws(RuntimeException::class, 'HTTP 500');

    it('throws when the response body has no result array', function () {
        Http::fake(['*/locations/onelineaddress*' => Http::response(['errors' => ['boom']])]);

        (new CensusGeocoderService())->oneLineAddress('123 Main St');
    })->throws(RuntimeException::class, 'unexpected response shape');

    it('throws on a connection exception', function () {
        Http::fake([
            '*/locations/onelineaddress*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        (new CensusGeocoderService())->oneLineAddress('123 Main St');
    })->throws(RuntimeException::class, 'connection failed');

});
