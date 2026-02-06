<?php

namespace App\Console\Commands;

use App\Models\Mill;
use App\Models\WoodSpecies;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MapMillsToWoodSpecies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:mill-species';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and WoodSpecies';

    /**
     * maximum errors before aborting
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
         * initial conditions
         */
        $millsWithoutSpecies = Mill::doesntHave('woodSpecies')->count();
        $totalMills = Mill::all()->count();

        $this->info(sprintf(
            '%d mills without wood species out of %d total mills.',
            $millsWithoutSpecies,
            $totalMills
        ));

        /**
         * Allow 10% errors
         */
        $this->maxErrors = ceil($millsWithoutSpecies * 0.1);

        $woodSpecies = WoodSpecies::all()
            ->pluck('id', 'name')
            ->toArray();

        $audit = [
            'success' => 0,
            'fail' => 0,
        ];

        Mill::chunkById(100, function (Collection $mills) use ($woodSpecies, $audit) {
            foreach ($mills as $mill) {
                try {
                    /**
                     * check for empty mill->species
                     */
                    if (empty($mill->species)) {
                        $msg = sprintf(
                            'Mill #%d (%s) has an empty species field.',
                            $mill->id,
                            $mill->mill_name
                        );
                        $this->warn($msg);
                        Log::warning($msg);
                        continue;
                    }

                    $millSpecies = explode('|', trim($mill->species));

                    foreach ($millSpecies as $species) {
                        if (empty($woodSpecies[$species])) {
                            $msg = sprintf(
                                'Mill %d (%s) has an unknown wood species "%s".',
                                $mill->id,
                                $mill->mill_name,
                                $species
                            );
                            throw new Exception($msg);
                        }

                        /**
                         * InteractsWithPivotTable::attach() does not return a value!
                         */
                        $mill->woodSpecies()->attach($woodSpecies[$species], ['created_at' => now(), 'updated_at' => now()]);

                        $msg = sprintf(
                            'Attached WoodSpecies "%s" to Mill #%d (%s)!',
                            $species,
                            $mill->id,
                            $mill->mill_name
                        );
                        $this->info($msg);
                        Log::info($msg);
                    }

                } catch (Exception $e) {
                    $this->error($e->getMessage());
                    Log::error($e->getMessage());
                    $audit['fail']++;
                }

                /**
                 * abort if too many errors
                 */
                if ($this->maxErrors < $audit['fail']) {
                    $msg = sprintf(
                        'Aborting because too many errors: %d of %d allowed.',
                        $audit['fail'],
                        $this->maxErrors
                    );
                    $this->error($msg);
                    Log::error($msg);
                    return false;
                }
            }
        });
    }
}
