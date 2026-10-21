<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ProvidesLocationOptions
{
    protected function locationOptions($stateId): array
    {
        return [
            'countries' => Country::where('chrPublish', 'Y')->orderBy('countryname')->get(['id', 'countryname']),
            'states' => State::orderBy('name')->get(['id', 'name']),
            'cities' => $stateId
                ? City::where('state_id', (int) $stateId)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    protected function cityInSelectedStateRule(Request $request): Exists
    {
        return Rule::exists('cities', 'id')->where(function ($query) use ($request) {
            if ($request->filled('state')) {
                $query->where('state_id', (int) $request->input('state'));
            }
        });
    }
}
