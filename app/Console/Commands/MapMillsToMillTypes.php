<?php

namespace App\Console\Commands;

use App\Models\Mill;
use App\Models\MillType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MapMillsToMillTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:mill-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and MillTypes';

    /**
     * How many errors is too many?
     * 
     * @var int
     */
    protected $maxErrors = 10;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * This one can't help but be iterative.
         * Which makes it a good candidate for queued jobs...
         */

        $millCount = Mill::doesntHave('millTypes')->count();
        $totalMills = Mill::all()->count();

        /**
         * let's say 10% max errors
         */
        $this->maxErrors = ceil($millCount * 0.1);

        $this->info(sprintf(
            '%d mills without mill type out of %d total mills.',
            $millCount,
            $totalMills
        ));

        $millTypes = MillType::all()->keyBy('name')->toArray();
        
        $audit = [
            'success' => 0,
            'fail' => 0,
        ];

        // dd($millTypes);

        Mill::chunkById(100, function (Collection $mills) use ($millTypes, $audit) {
            /**
             * If we were trying to be slick, we'd use a Job Queue to process these
             */
            foreach ($mills as $mill) {
                try {
                    /**
                     * We need to check for $mill->type being empty
                     */
                    if (empty(trim($mill->type))) {
                        $msg = sprintf(
                            'Mill #%d (%s) has an empty type field!',
                            $mill->id,
                            $mill->mill_name
                        );
                        $this->warn($msg);
                        Log::warning($msg);
                        continue;
                    }

                    $type = explode('|', trim($mill->type));

                    $this->info(sprintf(
                        'Mill #%d (%s) has %d type(s): "%s"',
                        $mill->id,
                        $mill->mill_name,
                        count($type),
                        // $mill->type
                        implode(', ', $type)
                    ));

                    foreach ($type as $t) {
                        // if (! $millTypes->has($t)) {
                        if (! array_key_exists($t, $millTypes)) {
                            $msg = sprintf(
                                'Mill #%d (%s) has an unknown mill type: "%s"',
                                $mill->id,
                                $mill->mill_name,
                                $t
                            );
                            throw new Exception($msg);
                        }

                        /**
                         * InteractsWithPivotTable::attach() does not return anything!
                         */
                        
                        $mill->millTypes()->attach($millTypes[$t]['id'], ['created_at' => now(), 'updated_at' => now()]);

                        // log and output successes
                        $msg = sprintf(
                            'Attached mill type #%d (%s) to mill #%d (%s).',
                            $millTypes[$t]['id'],
                            $t,
                            $mill->id,
                            $mill->name
                        );
                        $this->info($msg);
                        Log::info($msg);
                        $audit['success']++;
                    }
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                    Log::error($e->getMessage());
                    $audit['fail']++;
                    // continue;
                }

                /**
                 * abort if there have been too many errors
                 * return false from the closure to abort the chunking loop
                 */
                if ($this->maxErrors < $audit['fail']) {
                    $msg = sprintf(
                        'Too many errors to continue: %d of %d.',
                        $audit['fail'],
                        $this->maxErrors
                    );
                    return false;
                }
            }
        });        
    }
}
