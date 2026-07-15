<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'states' => State::select('id', 'name', 'country_id')->orderBy('name')->get(),
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')->where(
                    fn ($query) => $query->where('state_id', $request->input('state_id'))
                ),
            ],
        ]);

        $state = State::findOrFail($validated['state_id']);
        $validated['country_id'] = $state->country_id;

        $city = City::create($validated);

        return response()->json([
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
                'state_id' => $city->state_id,
                'country_id' => $city->country_id,
            ],
        ], 201);
    }
}
