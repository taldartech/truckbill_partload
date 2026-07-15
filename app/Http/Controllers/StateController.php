<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StateController extends Controller
{
    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'countries' => Country::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('states', 'name')->where(
                    fn ($query) => $query->where('country_id', $request->input('country_id'))
                ),
            ],
            'code' => 'nullable|string|max:255',
        ]);

        $state = State::create($validated);

        return response()->json([
            'state' => [
                'id' => $state->id,
                'name' => $state->name,
                'country_id' => $state->country_id,
                'code' => $state->code,
            ],
        ], 201);
    }
}
