<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Patients;

class AuthenticateToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }

            // Decrypt token to get patient ID
            $decodedData = decrypt($token);
            $patientId = $decodedData['id'] ?? null;

            // Find the patient
            $patient = Patients::find($patientId);

            // Check if the token is still valid
            if (!$patient || $patient->remember_token !== $token) {
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }

            // Continue to the next request
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthorized request',
                'data' => [
                    'error' => 'Unauthorized request'
                ]
            ], 401);
        }
    }
}

