<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMillRequest;
use App\Http\Requests\UpdateMillRequest;
use App\Models\Mill;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $mills = Mill::all();

        return Inertia::render('mill-list', [
            'mills' => $mills,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMillRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Mill $mill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMillRequest $request, Mill $mill)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mill $mill)
    {
        //
    }
}
