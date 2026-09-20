<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{

    public function index()
    {
        $settings = GeneralSetting::pluck('field_value', 'field_name');

        $appUserOfferEnabled = ($settings['app_user_offer_enabled'] ?? '0') === '1';
        $appUserFreeSlots = (int) ($settings['app_user_free_slots'] ?? 5);

        return view('admin.settings', compact(
            'settings',
            'appUserOfferEnabled',
            'appUserFreeSlots'
        ));
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
            ['field_name' => 'affiliate_commission_percentage'],
            ['field_value' => $request->affiliate_commission_percentage ?? '3']
        );

        GeneralSetting::updateOrCreate(
            ['field_name' => 'reset_book_date'],
            ['field_value' => $request->reset_book_date]
        );
        return redirect()
            ->route('admin.settings')
            ->with('success', 'Settings updated successfully.')
            ->with('active_tab', 'general');

    }

    public function updateAppUserOffer(Request $request)
    {
        $validated = $request->validate([
            'app_user_free_slots' => 'required|integer|min:0|max:999',
        ]);

        $enabled = $request->boolean('app_user_offer_enabled') ? '1' : '0';

        GeneralSetting::updateOrCreate(
            ['field_name' => 'app_user_offer_enabled'],
            ['field_value' => $enabled]
        );

        GeneralSetting::updateOrCreate(
            ['field_name' => 'app_user_free_slots'],
            ['field_value' => (string) $validated['app_user_free_slots']]
        );

        return redirect()
            ->route('admin.settings')
            ->with('success', 'App user offer settings updated successfully.')
            ->with('active_tab', 'offer');
    }

}
