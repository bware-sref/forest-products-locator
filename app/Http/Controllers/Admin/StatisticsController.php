<?php

namespace App\Http\Controllers\Admin;

use App\Models\Mill;
use Illuminate\Routing\Controller;

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
        $millCounts = Mill::counts();

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
        $millCounts = Mill::counts();

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
}
