<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{

    public function index(){
        $settings = GeneralSetting::pluck('field_value', 'field_name');
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request){
        $request->validate([
            'time_duration' => 'required',          
        ]);

        // Update or create settings
       GeneralSetting::updateOrCreate(
            ['field_name' => 'time_duration'],
            ['field_value' => $request->time_duration]
        );
        
        GeneralSetting::updateOrCreate(
            ['field_name' => 'percentage'],
            ['field_value' => $request->percentage]
        );

        GeneralSetting::updateOrCreate(
            ['field_name' => 'reset_book_date'],
            ['field_value' => $request->reset_book_date]
        );
        return redirect()->back()->with('success', 'Settings updated successfully.');

    }

}
