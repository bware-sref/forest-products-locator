<?php

namespace App\Console\Commands;

use App\Enums\AwsLocationIntendedUse;
use App\Services\GeocodingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('zed:geocode
    {--query= : text to search}
    {--limit=1 : maxmimum number of results}
    {--x= : longitude, x-coordinate of BiasPosition. Number between -180 and 180. Both x and y must be supplied or defaults used.}
    {--y= : latitude, y-coordinate of BiasPosition. Number between -90 and 90. Both x and y must be supplied or defaults used.}
    {--noBias : Execute the lookup without any BiasPosition at whatsoever. Causes any x & y options supplied to be ignored. }
')]
#[Description('Execute a geocode lookup for the given query text with an optional limit.')]
class Geocode extends Command implements PromptsForMissingInput
{
    /**
     * Execute the console command.
     */
    public function handle(GeocodingService $geo)
    {
        $this->newLine();

        if (empty($this->option('query'))) {
            $this->error('The "query" option cannot be empty. Exiting...');
            return parent::FAILURE;
        }        

        /**
         * We only need to add options for BiasPosition because this usage is always SingleUse since we never store these results.
         * 
         * Put params in an array so we can pass them with spread operator.
         * That's useful because we may or may not have biasPosition.
         */
        $params = [
            'queryText' => $this->option('query'),
            'maxResults' => $this->option('limit'),
            'intendedUse' => AwsLocationIntendedUse::SingleUse->value,
            /**
             * Pass through whatever was passed for x and y.
             * If they're invalid, the default will be used instead.
             */
            'biasPosition' => [$this->option('x'), $this->option('y')],
        ];

        /**
         * --noBias is false unless included, so true means remove biasPosition from $params.
         * Actually, we don't want to remove it, but instead set it to false.
         */
        if ($this->option('noBias')) {
            $params['biasPosition'] = false;

            $this->comment('Omitting BiasPosition...');
        }

        $this->info('Doing geocode lookup with the following parameters: ');
        /**
         * Just use dump(), clown.
         * It formats arrays better than other options and it doesn't throw an error on sub-arrays.
         */
        dump($params);

        $this->newLine();

        $result = $geo->geocode(...$params);

        if (empty($result)) {
            $this->error('Empty result?!?');
            dump($params);
            return parent::FAILURE;
        }

        $this->newLine();

        /**
         * dump() output is so much easier to read than table
         */
        $this->info('Geocode lookup result:');
        dump($result);

        return parent::SUCCESS;
    }
}
