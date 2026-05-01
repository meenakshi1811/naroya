<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    public function listData()
    {
        $countries = Country::where('chrPublish', 'Y')->get();
        return view('admin.countries', compact('countries'));
    }

    public function create()
    {
        return view('admin.countries-create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCountry($request);

        Country::create($validated);

        return redirect()->route('admin.country')
            ->with('success', 'Country created successfully.');
    }

    public function edit($id)
    {
        $country = Country::findOrFail($id);
        return view('admin.countries-edit', compact('country'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateCountry($request);

        $country = Country::findOrFail($id);
        $country->update($validated);

        return redirect()->route('admin.country')
            ->with('success', 'Country updated successfully.');
    }

    public function delete($id)
    {
        Country::findOrFail($id)->delete();

        return redirect()->route('admin.country')
            ->with('success', 'Country deleted successfully.');
    }

    
    private function validateCountry(Request $request)
    {
        $data = $request->validate([
            'countrycode' => 'required|string|max:10',
            'countryname' => 'required|string|max:255',
            'code'        => 'required|string|max:10',
            'phonecode'   => 'required|string|max:10',
        ]);

        // Handle publish flag cleanly
        $data['chrPublish'] = $request->boolean('chrPublish') ? 'Y' : 'N';

        return $data;
    }
}