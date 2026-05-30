<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function byState(State $state): JsonResponse
    {
        $cities = City::select('id', 'name')
            ->where('state_id', $state->id)
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }
}
