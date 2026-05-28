<?php

namespace App\Http\Controllers\Admin;

use App\Models\Mill;
use App\Models\State;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

/**
 * Class StatisticsController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StatisticsController extends Controller
{
    public function index()
    {
        $millCounts = Mill::counts();

        return view('admin.statistics.index', [
            'title' => 'Statistics',
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Statistics' => false,
            ],
            'page' => 'resources/views/admin/statistics/index.blade.php',
            'controller' => 'app/Http/Controllers/Admin/StatisticsController.php',
            'millCounts' => $millCounts,
        ]);
    }

    public function updated()
    {
        // $millCounts = Mill::updates();
        $millCounts = self::collectMillChanges(self::getTimeframes(), 'updated');

        return view('admin.statistics.updated', [
            'title' => 'Updated :: Statistics',
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Statistics' => false,
            ],
            'page' => 'resources/views/admin/statistics/updated.blade.php',
            'controller' => 'app/Http/Controllers/Admin/StatisticsController.php',
            'millCounts' => $millCounts,
        ]);
    }

    public function additions()
    {
        // $millCounts = Mill::counts();

        $millCounts = self::collectMillChanges(self::getTimeframes(), 'created');

        return view('admin.statistics.additions', [
            'title' => 'Additions :: Statistics',
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Statistics' => false,
            ],
            'page' => 'resources/views/admin/statistics/additions.blade.php',
            'controller' => 'app/Http/Controllers/Admin/StatisticsController.php',
            'millCounts' => $millCounts,
        ]);
    }

    protected static function getTimeframes(): array
    {
        return [
            'Last Week' => Carbon::now()->minus(weeks: 1),
            'Last Month' => Carbon::now()->minus(months: 1),
            'Last Three Months' => Carbon::now()->minus(months: 3),
            'Last Year' => Carbon::now()->minus(years: 1),
        ];
    }

    protected static function collectMillChanges(array $timeFrames, string $action = 'updated'): array
    {   
        $data = [];

        // $totalMillCount = Cache::memo()->get('totalMillCount');
        $totalMillCount = Mill::countAll();

        $stateMillCounts = State::millCounts();

        // dd($stateMillCounts);

        foreach ($timeFrames as $key => $timeFrame) {
            // here's where we need to know which method to invoke
            $number = 'updated' === $action ? Mill::updatedSince($timeFrame) : Mill::createdSince($timeFrame);

            $total = [
                'number' => $number,
                'percentage' => ($number / $totalMillCount) * 100,
            ];

            $data[$key] = [
                'total' => $total,
                'since' => $timeFrame->toDateTimeString(),
            ];
            
            /**
             * If the total is 0 for this timeframe, there's no point in even query changes by state.
             * Similarly, if the percentage is 100%, there's no point in query changes by state.
             */
            if ($number < 1) {
                continue;
            }

            $byState = 'updated' === $action ? State::millsUpdatedSince($timeFrame) : State::millsCreatedSince($timeFrame);

            // dd(['byState' => $byState]);

            // dd([
            //     'is_array' => is_array($byState),
            //     'count' => count($byState),
            //     'byState' => $byState,
            // ]);

            /**
             * If no Mills have been changed, $byState will be an empty array.
             */
            if (is_array($byState) && count($byState) > 0) {
                // dd([
                //     'totalInTimeFrame' => $total,
                //     'count(ByState)' => count($byState),
                //     'byState' => $byState,
                //     'timeFrame' => $timeFrame->toDateTimeString(),
                //     'stateMillCounts' => $stateMillCounts,
                // ]);
                
                foreach ($byState as $state => $value) {
                    $millsInState = $stateMillCounts[$state];
                    $byState[$state] = [
                        'total' => $millsInState, // $stateMillCounts[$state]['mills_count'],
                        'number' => $value, // ['mills_count'],
                        'percentage' => ($value / $millsInState) * 100,
                    ];
                }
            }

            $data[$key]['byState'] = $byState;
        }
        return $data;
    }
}
