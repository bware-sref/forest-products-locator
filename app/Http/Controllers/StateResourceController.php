<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\StateResource;
use Illuminate\Http\Request;

class StateResourceController extends Controller
{
    /**
     * StateResource index is slightly different than other indices 
     * because instead of showing state resources, it shows States with resources and links to each States
     * byState() page.
     */
    public function index()
    {}

    public function show(StateResource $stateResource)
    {}

    public function byState(State $state)
    {}
}
