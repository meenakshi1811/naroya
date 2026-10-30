<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicTokenQueue;
use App\Models\Patients;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Token booking at a clinic, kept apart from PatientController::sendRequest so the
 * time-slot booking API stays untouched. The patient picks a token on the token map
 * instead of a time slot.
 */
class ClinicAppointmentController extends Controller
{
    /** Day window used when the clinic has no hours set (same default as the time-slot API). */
    private const DEFAULT_START = '09:00';
    private const DEFAULT_END = '21:00';

    /**
     * POST /api/patient/send-request-clinic with clinic_id, doctor_id, token_no,
     * optional name and optional date (Y-m-d, today or later; default today).
     */
    public function sendRequest(Request $request): JsonResponse
    {
        // AuthenticateToken has already verified this token.
        $patient = Patients::find(decrypt($request->bearerToken())['id'] ?? null);

        if (! $patient) {
            return $this->error('Unauthorized request', 401);
        }

        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|integer',
            'doctor_id' => 'required|integer',
            'token_no' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
        ], [
            'date.after_or_equal' => 'date must be today or a later date.',
            'date.date_format' => 'date must be in Y-m-d format.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $clinic = Clinic::where('is_active', true)->find((int) $request->input('clinic_id'));

        if (! $clinic) {
            return $this->error('Clinic not found.');
        }

        $doctor = User::doctors()
            ->whereKey((int) $request->input('doctor_id'))
            ->where('clinic_id', $clinic->id)
            ->where('chrApproval', 'Y')
            ->first();

        if (! $doctor) {
            return $this->error('This doctor is not part of the selected clinic.');
        }

        $tokenNo = (int) $request->input('token_no');

        if ($clinic->max_tokens && $tokenNo > $clinic->max_tokens) {
            return $this->error('Please choose a valid token number.');
        }

        // Same rule as the existing booking API: a posted name updates the patient's profile.
        $postedName = trim((string) $request->input('name', ''));
        if ($postedName !== '') {
            $patient->name = $postedName;
            $patient->save();
        }

        $date = $request->input('date') ?: Carbon::now()->toDateString();
        [$startTime, $endTime] = $this->clinicDayWindow($clinic);

        $appointment = DB::transaction(function () use ($clinic, $doctor, $patient, $date, $tokenNo, $startTime, $endTime) {
            // Same clinic row lock as the other booking paths, so two bookings can't take one token.
            Clinic::whereKey($clinic->id)->lockForUpdate()->first();

            // Tokens are numbered per doctor: another doctor's token 1 doesn't block this one.
            $takenTokens = Appointment::where('clinic_id', $clinic->id)
                ->where('dr_id', $doctor->id)
                ->where('varAppointment', $date)
                ->where('chrIsCanceled', 'N')
                ->where('chrIsRejected', 'N')
                ->whereNotNull('token_no')
                ->pluck('token_no')
                ->map(fn ($token) => (int) $token)
                ->flip();

            if ($clinic->max_tokens && $takenTokens->count() >= $clinic->max_tokens) {
                return 'No tokens are available with this doctor at ' . $clinic->name . ' on the selected date.';
            }

            if ($takenTokens->has($tokenNo)) {
                return 'Token ' . $tokenNo . ' is already booked. Please choose another token.';
            }

            $appointment = new Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->dr_id = $doctor->id;
            $appointment->clinic_id = $clinic->id;
            $appointment->token_no = $tokenNo;
            $appointment->amount = $doctor->varFees ?? null;
            $appointment->is_freeslot = 0;
            $appointment->varAppointment = $date;
            // A token is for the clinic's day, not a slot; the hours keep it visible in
            // the existing appointment lists, which filter on endTime.
            $appointment->startTime = $startTime;
            $appointment->endTime = $endTime;
            $appointment->varSympton = '';
            $appointment->varSymptondesc = '';
            $appointment->save();

            return $appointment;
        });

        if (is_string($appointment)) {
            return $this->error($appointment);
        }

        if ($doctor->fcm_token) {
            (new NotificationController())->sendPushNotification(
                $doctor->fcm_token,
                'New Appointment Request',
                'You have a new appointment request from ' . $patient->name . ' (token ' . $tokenNo . ')',
                'doctor',
                [
                    'type' => 'new_appointment_request',
                    'appointmentId' => (string) $appointment->id,
                ],
                'new_appointment_request'
            );
        }

        return response()->json([
            'message' => 'Appointment request sent!',
            'data' => [
                'appointment' => $appointment,
                'token_no' => $tokenNo,
                'clinic' => ['id' => $clinic->id, 'name' => $clinic->name],
            ],
        ], 200);
    }

    /**
     * GET|POST /api/patient/my-clinic-tokens with optional clinic_id, doctor_id and date (Y-m-d).
     * The logged-in patient's booked clinic tokens: upcoming ones (today onward) by default,
     * or only the given date. Each comes with its appointment_id, needed to cancel it.
     */
    public function myTokens(Request $request): JsonResponse
    {
        // AuthenticateToken has already verified this token.
        $patient = Patients::find(decrypt($request->bearerToken())['id'] ?? null);

        if (! $patient) {
            return $this->error('Unauthorized request', 401);
        }

        $validator = Validator::make($request->all(), [
            'clinic_id' => 'nullable|integer',
            'doctor_id' => 'nullable|integer',
            'date' => 'nullable|date_format:Y-m-d',
        ], [
            'date.date_format' => 'date must be in Y-m-d format.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $appointments = Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereNotNull('clinic_id')
            ->whereNotNull('token_no')
            ->where('chrIsCanceled', 'N')
            ->where('chrIsRejected', 'N')
            ->when(
                $request->filled('date'),
                fn ($query) => $query->where('varAppointment', $request->input('date')),
                fn ($query) => $query->where('varAppointment', '>=', Carbon::now()->toDateString())
            )
            ->when($request->filled('clinic_id'), fn ($query) => $query->where('clinic_id', (int) $request->input('clinic_id')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('dr_id', (int) $request->input('doctor_id')))
            ->with(['clinic:id,name', 'doctor:id,name,surname'])
            ->orderBy('varAppointment')
            ->orderBy('token_no')
            ->get();

        // Running token for each clinic, doctor and date in one query.
        $runningTokens = ClinicTokenQueue::whereIn('clinic_id', $appointments->pluck('clinic_id')->unique())
            ->whereIn('doctor_id', $appointments->pluck('dr_id')->unique())
            ->whereIn('queue_date', $appointments->pluck('varAppointment')->unique())
            ->get()
            ->mapWithKeys(fn (ClinicTokenQueue $queue) => [
                $queue->clinic_id . '|' . $queue->doctor_id . '|' . Carbon::parse($queue->queue_date)->toDateString() => (int) $queue->current_token,
            ]);

        $tokens = $appointments->map(fn (Appointment $appointment) => [
            'appointment_id' => $appointment->id,
            'token_no' => (int) $appointment->token_no,
            'date' => $appointment->varAppointment,
            'clinic_id' => (int) $appointment->clinic_id,
            'clinic_name' => $appointment->clinic?->name,
            'doctor_id' => (int) $appointment->dr_id,
            'doctor_name' => $appointment->doctor ? trim($appointment->doctor->name . ' ' . ($appointment->doctor->surname ?? '')) : null,
            'status' => $appointment->chrIsAccepted === 'Y' ? 'accepted' : 'pending',
            'running_token' => $runningTokens[$appointment->clinic_id . '|' . $appointment->dr_id . '|' . $appointment->varAppointment] ?? 0,
        ])->values();

        return response()->json([
            'message' => 'success',
            'data' => [
                'tokens' => $tokens,
            ],
        ], 200);
    }

    /**
     * POST /api/patient/cancel-appointment-clinic with appointment_id and an optional reason.
     * Same as /patient/cancel-appointment for a clinic booking, but it also frees the
     * token and only lets the patient cancel their own appointment.
     */
    public function cancel(Request $request): JsonResponse
    {
        // AuthenticateToken has already verified this token.
        $patient = Patients::find(decrypt($request->bearerToken())['id'] ?? null);

        if (! $patient) {
            return $this->error('Unauthorized request', 401);
        }

        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $appointment = Appointment::with('doctor')
            ->whereKey((int) $request->input('appointment_id'))
            ->where('patient_id', $patient->id)
            ->whereNotNull('clinic_id')
            ->first();

        if (! $appointment) {
            return $this->error('Clinic appointment not found.');
        }

        if ($appointment->chrIsCanceled === 'Y') {
            return $this->error('This appointment is already cancelled.');
        }

        if ($appointment->chrIsRejected === 'Y') {
            return $this->error('This appointment was rejected by the doctor and cannot be cancelled.');
        }

        $releasedToken = $appointment->token_no !== null ? (int) $appointment->token_no : null;
        $reason = trim((string) $request->input('reason', ''));

        $changes = [
            'chrIsCanceled' => 'Y',
            'token_no' => null,
        ];

        // Without a reason the column is left as it is rather than set to NULL.
        if ($reason !== '') {
            $changes['varCancelReason'] = $reason;
        }

        $appointment->update($changes);

        $doctor = $appointment->doctor;

        if ($doctor && $doctor->fcm_token) {
            (new NotificationController())->sendPushNotification(
                $doctor->fcm_token,
                'Appointment Canceled',
                "The appointment with {$patient->name}" . ($releasedToken ? " (token {$releasedToken})" : '') . ' has been canceled.' . ($reason !== '' ? " Reason: {$reason}" : ''),
                'doctor',
                [
                    'type' => 'appointment_canceled',
                    'appointmentId' => (string) $appointment->id,
                ],
                'appointment_canceled'
            );
        }

        return response()->json([
            'message' => 'Appointment cancelled successfully!',
            'data' => [
                'appointment_id' => $appointment->id,
                'clinic_id' => $appointment->clinic_id,
                'token_no' => $releasedToken,
                // Same patient block as /patient/cancel-appointment returns.
                'user' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'lastname' => $patient->lastname,
                    'country' => $patient->country,
                    'state' => $patient->state,
                    'email' => $patient->email,
                    'varProfile' => $patient->varProfile
                        ? config('app.url') . 'api/patientprofile/' . $patient->varProfile
                        : 'null',
                ],
            ],
        ], 200);
    }

    /** First session start to last session end, or the default day window. */
    private function clinicDayWindow(Clinic $clinic): array
    {
        $sessions = collect($clinic->timings ?? [])->sortBy('start')->values();

        if ($sessions->isEmpty()) {
            return [self::DEFAULT_START, self::DEFAULT_END];
        }

        return [$sessions->first()['start'], $sessions->max('end')];
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [
                'error' => $message,
            ],
        ], $status);
    }
}
