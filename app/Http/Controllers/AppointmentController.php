<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patients;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Controllers\NotificationController;

class AppointmentController extends Controller
{

    public function cancelAppointment(Request $request)
    {
        $tokenData = $this->getTokenData($request);

        if (!$tokenData) {
            return $this->unauthorized();
        }

        try {
            $patient = Patients::find($tokenData['id']);

            $request->validate([
                'appointment_id' => 'required',
                'reason' => 'required'
            ]);

            $appointment = Appointment::with('doctor')->find($request->appointment_id);

            if ($appointment) {
                $appointment->update([
                    'chrIsCanceled' => 'Y',
                    'varCancelReason' => $request->reason
                ]);

                $doctor = $appointment->doctor;

                if ($doctor && $doctor->fcm_token) {
                    (new NotificationController())->sendPushNotification(
                        $doctor->fcm_token,
                        "Appointment Canceled",
                        "The appointment with {$patient->name} has been canceled. Reason: {$request->reason}",
                        'doctor'
                    );
                }

                return response()->json([
                    'message' => 'Appontment cancel succesfully!',
                    'data' => [
                        'user' => $this->formatPatient($patient)
                    ]
                ], 200);
            }

        } catch (\Exception $e) {
            return $this->errorResponse();
        }

        return $this->errorResponse();
    }

    public function handlePrescription(Request $request)
    {
        try {
            $request->validate([
                'appointment_id' => 'required',
                'symptons' => 'required',
                'prescription' => 'required'
            ]);

            $user = $request->user();

            // eager load org experience (if relation exists)
            $user->load('experiences');

            $doctor = $this->formatDoctor($user);

            $appointment = Appointment::find($request->appointment_id);

            if ($appointment) {
                $appointment->update([
                    'typeSympton' => $request->symptons,
                    'varPrescription' => $request->prescription
                ]);

                return response()->json([
                    'message' => 'Prescription added Succesfully!',
                    'data' => [
                        'doctor' => $doctor
                    ]
                ], 200);
            }

            return $this->errorResponse();

        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    public function getAppointmentData(Request $request)
    {
        $tokenData = $this->getTokenData($request);

        if (!$tokenData) {
            return $this->unauthorized();
        }

        try {
            $patient = Patients::find($tokenData['id']);

            $today = Carbon::today();
            $currentDateTime = now();

            $pageSize = max(1, min((int) $request->input('pageSize', 10), 100));
            $upcomingPage = max(1, (int) $request->input('upcomingPageNumber', 1));
            $pastPage = max(1, (int) $request->input('pastPageNumber', 1));

            $shouldPaginateUpcoming = $request->filled('upcomingPageNumber') || $request->filled('pageSize');
            $shouldPaginatePast = $request->filled('pastPageNumber') || $request->filled('pageSize');

            //Eager loading (NO JOIN)
            $baseQuery = Appointment::with(['doctor.categoryRel'])
                ->where('patient_id', $patient->id)
                ->where('charIsPaid', 'Y')
                ->where('chrIsAccepted', 'Y')
                ->where('chrIsCanceled', 'N');

            //Upcoming
            $nextQuery = (clone $baseQuery)
                ->whereDate('varAppointment', '>=', $today)
                ->where(function ($q) use ($currentDateTime) {
                    $q->whereTime('endTime', '>=', $currentDateTime->format('H:i'))
                      ->orWhereDate('varAppointment', '>', $currentDateTime->format('Y-m-d'));
                })
                ->orderBy('varAppointment', 'asc')
                ->orderBy('startTime', 'asc');

            $nextData = $shouldPaginateUpcoming
                ? $nextQuery->paginate($pageSize, ['*'], 'upcomingPage', $upcomingPage)
                : $nextQuery->get();

            // Past
            $pastQuery = (clone $baseQuery)
                ->whereDate('varAppointment', '<=', $today)
                ->where(function ($q) use ($currentDateTime) {
                    $q->whereTime('endTime', '<', $currentDateTime->format('H:i'))
                      ->orWhereDate('varAppointment', '<', $currentDateTime->format('Y-m-d'));
                })
                ->orderBy('varAppointment', 'desc')
                ->orderBy('startTime', 'desc');

            $pastData = $shouldPaginatePast
                ? $pastQuery->paginate($pageSize, ['*'], 'pastPage', $pastPage)
                : $pastQuery->get();

            return response()->json([
                'message' => 'Success!',
                'data' => [
                    'user' => $this->formatPatient($patient),
                    'upcomingAppointment' => $this->formatAppointments($nextData, false),
                    'pastAppointment' => $this->formatAppointments($pastData, true),
                    'upcomingPagination' => $shouldPaginateUpcoming ? $this->formatPagination($nextData) : new \stdClass(),
                    'pastPagination' => $shouldPaginatePast ? $this->formatPagination($pastData) : new \stdClass(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    

    private function getTokenData($request)
    {
        try {
            $header = $request->header('Authorization');
            $token = explode('Bearer ', $header)[1] ?? null;
            return $token ? decrypt($token) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatPatient($patient)
    {
        return [
            'id' => $patient->id,
            'name' => $patient->name,
            'lastname' => $patient->lastname,
            'country' => $patient->country,
            'state' => $patient->state,
            'email' => $patient->email,
            'varProfile' => $patient->varProfile
                ? config('app.url') . 'api/patientprofile/' . $patient->varProfile
                : 'null'
        ];
    }

    private function formatAppointments($data, $includePrescription = false)
    {
        $items = method_exists($data, 'items') ? $data->items() : $data;

        return collect($items)->map(function ($a) use ($includePrescription) {
            $doctor = $a->doctor;

            $response = [
                'id' => $a->id,
                'startTime' => $a->startTime,
                'endTime' => $a->endTime,
                'appointmentDate' => $a->varAppointment,
                'sympton' => $a->varSympton,
                'sympton_description' => $a->varSymptondesc,
                'IsPaid' => $a->charIsPaid,
                'DoctorName' => $doctor->name . ' ' . $doctor->surname,
                'category' => $doctor->categoryRel->title ?? null,
                'amount' => $doctor->varFees,
                'profile_picture' => config('app.url') . 'api/docterprofile/' . $doctor->varProfile,
            ];

            if ($includePrescription) {
                $response['Presc_Sympton'] = $a->typeSympton;
                $response['Prescription'] = $a->varPrescription;
            }

            return $response;
        });
    }

    private function formatPagination($data)
    {
        return [
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
        ];
    }

    private function unauthorized()
    {
        return response()->json([
            'message' => 'Unauthorized request',
            'data' => ['error' => 'Unauthorized request']
        ], 401);
    }

    private function errorResponse()
    {
        return response()->json([
            'message' => 'Please Provide Valid details!',
            'data' => ['error' => 'Please Provide Valid details!']
        ], 400);
    }

    private function formatDoctor($user)
    {
        return [
            'id' => $user->id,
            'first_name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'profile_picture' => $user->varProfile
                ? config('app.url') . 'api/docterprofile/' . $user->varProfile
                : null,
            'current_work_org' => json_encode($user->experiences ?? [])
        ];
    }
}