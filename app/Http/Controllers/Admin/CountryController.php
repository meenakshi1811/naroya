<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    public function listData()
    {        
        $countries = Country::where('chrPublish','Y')->get();
        return view('admin.countries',compact(['countries'])); // Create this view
    }


    public function create()
    {
        return view('admin.countries-create'); // Make sure this path matches your view structure
    }

    public function store(Request $request)
    {
        $request->validate([
            'countrycode' => 'required|string',
            'countryname' => 'required|string|max:255',
            'code' => 'required|string|max:3',
            'phonecode' => 'required|string',
        ]);
        if(!empty($request->input('chrPublish')) && $request->input('chrPublish') == 1 ){
            $publish = 'Y';
        }else{
            $publish = 'N';
        }
        $countryData = [
            'countrycode' => $request->input('countrycode'),
            'countryname' => $request->input('countryname'),
            'code' => $request->input('code'),
            'phonecode' => $request->input('phonecode'),
            'chrPublish' => $publish, // Default to false if not set
        ];
    
        // Create a new country record
        Country::create($countryData);

        // Country::create($request->all());

        return redirect()->route('admin.country')->with('success', 'Country created successfully.');
    }

    // public function edit($id)
    //     {
    //         $country = Country::findOrFail($id);
    //         return view('admin.spciality-action', compact('country'));
    //     }

    //     public function update(Request $request, $id)
    //     {
    //         $request->validate([
    //             'name' => 'required|string|max:255',
    //             // 'description' => 'required|string',
    //             // Add other validation rules as needed
    //         ]);
    //         $specialityArr = [];
    //         $specialityArr['title'] = isset($request->name)? $request->name : '';
    //         $speciality = Country::findOrFail($id);
    //         $speciality->update($specialityArr);

    //         return redirect()->route('admin.country')->with('success', 'Country updated successfully.');
    //     }




        public function edit($id)
    {
        $country = Country::findOrFail($id); // Fetch the country by ID
        return view('admin.countries-edit', compact('country'));
    }

    // Update the specified country
    public function update(Request $request, $id)
    {
        $request->validate([
            'countrycode' => 'required|string|max:10',
            'countryname' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'phonecode' => 'required|string|max:10',            
        ]);

        $country = Country::findOrFail($id);
        if(!empty($request->input('chrPublish')) && $request->input('chrPublish') == 1 ){
            $publish = 'Y';
        }else{
            $publish = 'N';
        }
        // Create an associative array with the validated fields
        $countryData = [
            'countrycode' => $request->input('countrycode'),
            'countryname' => $request->input('countryname'),
            'code' => $request->input('code'),
            'phonecode' => $request->input('phonecode'),
            'chrPublish' => $publish,
        ];

        $country->update($countryData); // Update the country record

        return redirect()->route('admin.country')->with('success', 'Country updated successfully.');
    }

        public function delete($id)
        {
            $resource = Country::findOrFail($id);
            $resource->delete();

            return redirect()->route('admin.country')->with('success', 'Country deleted successfully.');
        }

}
