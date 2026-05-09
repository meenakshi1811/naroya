<?php

namespace App\Http\Controllers;

use App\Models\DoctorBankDetail;
use Illuminate\Http\Request;

class DoctorBankDetailController extends Controller
{
    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'account_type' => 'required|in:Savings,Current',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:20',
        ]);

        $doctorId = $request->user()->id;

        $bankDetail = DoctorBankDetail::updateOrCreate(
            ['doctor_id' => $doctorId],
            $validated
        );

        return response()->json([
            'message' => 'Bank details saved successfully.',
            'data' => $bankDetail,
        ], 200);
    }

    public function showByDoctorId($doctorId)
    {
        $bankDetail = DoctorBankDetail::where('doctor_id', $doctorId)->first();

        if (!$bankDetail) {
            return response()->json([
                'message' => 'Bank details not found.',
                'data' => new \stdClass(),
            ], 404);
        }

        return response()->json([
            'message' => 'Bank details fetched successfully.',
            'data' => $bankDetail,
        ], 200);
    }
}
