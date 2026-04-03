<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Speciality;

class SpecialityController extends Controller
{
    public function listData()
    {        
        $speciality = Speciality::get();
        return view('admin.speciality',compact(['speciality'])); // Create this view
    }


    public function create()
    {
        return view('admin.spciality-action'); // Make sure this path matches your view structure
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create a new speciality
        Speciality::create([
            'title' => $request->name,
        ]);

        // Redirect back with a success message
        return redirect()->route('admin.speciality')->with('success', 'Speciality created successfully.');
    }

    public function edit($id)
        {
            $speciality = Speciality::findOrFail($id);
            return view('admin.spciality-action', compact('speciality'));
        }

        public function update(Request $request, $id)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                // 'description' => 'required|string',
                // Add other validation rules as needed
            ]);
            $specialityArr = [];
            $specialityArr['title'] = isset($request->name)? $request->name : '';
            $speciality = Speciality::findOrFail($id);
            $speciality->update($specialityArr);

            return redirect()->route('admin.speciality')->with('success', 'Speciality updated successfully.');
        }


        public function delete($id)
        {
            $resource = Speciality::findOrFail($id);
            $resource->delete();

            return redirect()->route('admin.speciality')->with('success', 'Speciality deleted successfully.');
        }



}
