<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicTokenQueue;
use App\Models\Patients;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Running ("now serving") token of a doctor's daily queue at a clinic. Tokens are
 * issued per clinic, doctor and date when a patient sends a booking request, so two
 * doctors of one clinic each have their own token 1; clinic staff move a doctor's
 * running token forward and both apps read it.
 */
class ClinicTokenController extends Controller
{
    /**
     * How long the running token read is cached. The number only moves when staff
     * advance it, and those writes clear the cache, so a few seconds of staleness on
     * the (heavily polled) read path is safe and spares the DB a query per poll.
     */
    private const RUNNING_TOKEN_TTL = 5;

    /** Patients within this many places after the running token get a "your turn is coming up" push. */
    private const REMINDER_POSITIONS = 5;

    /**
     * GET|POST /api/clinic-running-token with clinic_id and doctor_id (optional for a doctor or employee: defaults to their own).
     * Works with either a patient login token or a doctor login token.
     */
    public function running(Request $request): JsonResponse
    {
        $context = $this->readerContext($request);
        if ($context instanceof JsonResponse) {
            return $context;
        }

        return $this->success($this->runningToken($context['clinic'], $context['doctor_id']));
    }

    /**
     * GET|POST /api/clinic-tokens with clinic_id, doctor_id (optional for staff) and optional date (Y-m-d, default today).
     * The doctor's token map like a cinema seat plan: every token 1..max_tokens with its status.
     * Works with either a patient login token or a doctor login token.
     */
    public function tokens(Request $request): JsonResponse
    {
        $context = $this->readerContext($request, withDate: true);
        if ($context instanceof JsonResponse) {
            return $context;
        }

        $clinic = $context['clinic'];
        $doctorId = $context['doctor_id'];
        $date = $context['date'];
        $patient = $context['patient'];

        [$booked, $bookedTokens, $total] = $this->tokenBoard($clinic, $doctorId, $date);
        $myTokens = $patient
            ? $booked->where('patient_id', $patient->id)->pluck('token_no')->map(fn ($token) => (int) $token)->flip()
            : collect();

        $tokens = [];
        for ($number = 1; $number <= $total; $number++) {
            $tokens[] = [
                'token_no' => $number,
                'status' => $bookedTokens->has($number) ? 'booked' : 'available',
                'is_mine' => $myTokens->has($number),
            ];
        }

        return $this->success([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'date' => $date,
            'max_tokens' => $clinic->max_tokens,
            'total_tokens' => $total,
            'booked_count' => $bookedTokens->count(),
            'available_count' => $total - $bookedTokens->count(),
            'running_token' => $this->currentToken($clinic, $doctorId, $date),
            'tokens' => $tokens,
        ]);
    }

    /**
     * GET|POST /api/clinic-available-tokens with clinic_id, doctor_id (optional for staff) and optional date
     * (Y-m-d, default today): the doctor's token numbers still free, e.g. [20, 25, 30].
     * Works with either a patient login token or a doctor login token.
     */
    public function available(Request $request): JsonResponse
    {
        $context = $this->readerContext($request, withDate: true);
        if ($context instanceof JsonResponse) {
            return $context;
        }

        $clinic = $context['clinic'];
        [, $bookedTokens, $total] = $this->tokenBoard($clinic, $context['doctor_id'], $context['date']);

        $available = collect(range(1, $total))
            ->reject(fn (int $number) => $bookedTokens->has($number))
            ->values();

        return $this->success([
            'clinic_id' => $clinic->id,
            'doctor_id' => $context['doctor_id'],
            'date' => $context['date'],
            'max_tokens' => $clinic->max_tokens,
            'total_tokens' => $total,
            'booked_count' => $bookedTokens->count(),
            'available_count' => $available->count(),
            'available_tokens' => $available,
        ]);
    }

    /**
     * GET /api/doctor/clinic-token with optional date (Y-m-d, default today) and doctor_id
     * (see staffDoctorId): the doctor's running token at the user's clinic.
     */
    public function current(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $doctorId = $this->staffDoctorId($request, $clinic);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $date = $this->dateFrom($request);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        return $this->success($this->runningToken($clinic, $doctorId, $date));
    }

    /**
     * POST /api/doctor/clinic-token/increase (also /next) with doctor_id (see staffDoctorId):
     * move the doctor's running token for today up to their next booked token.
     * Cancelled and rejected tokens are skipped.
     */
    public function increase(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $doctorId = $this->staffDoctorId($request, $clinic);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $date = $this->today();
        $reminders = [];

        $advanced = DB::transaction(function () use ($clinic, $doctorId, $date, $request, &$reminders) {
            $queue = $this->lockQueue($clinic, $doctorId, $date);

            $nextToken = $this->activeTokens($clinic, $doctorId, $date)
                ->where('token_no', '>', $queue->current_token)
                ->min('token_no');

            if ($nextToken === null) {
                return false;
            }

            $queue->current_token = (int) $nextToken;
            $queue->updated_by = $request->user()->id;
            $queue->save();

            $reminders = $this->claimTokenReminders($clinic, $doctorId, $date, $queue->current_token);

            return true;
        });

        $data = $this->runningToken($clinic, $doctorId);
        $this->sendTokenReminders($clinic, $reminders);

        return $this->success(
            $data,
            $advanced ? 'Now serving token ' . $data['running_token'] . '.' : 'No more tokens are waiting.'
        );
    }

    /**
     * POST /api/doctor/clinic-token/update with clinic_id, doctor_id (see staffDoctorId) and
     * value = the token number to run next. The app works out + / − itself; any number from
     * 0 (reset) up to the clinic's max tokens is accepted, booked or not. clinic_id must be
     * the caller's clinic.
     */
    public function change(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|integer',
            'value' => 'required|integer|min:0',
        ]);

        if ($validator->errors()->has('clinic_id')) {
            return $this->error('clinic_id is required.');
        }

        if ($validator->fails()) {
            return $this->error('value must be a token number (0 or more).');
        }

        if ((int) $request->input('clinic_id') !== (int) $clinic->id) {
            return $this->error('You are not linked to this clinic.');
        }

        $doctorId = $this->staffDoctorId($request, $clinic);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $token = (int) $request->input('value');

        if ($clinic->max_tokens && $token > $clinic->max_tokens) {
            return $this->error('value cannot be more than ' . $clinic->max_tokens . ', the clinic\'s max tokens.');
        }

        $date = $this->today();

        $reminders = DB::transaction(function () use ($clinic, $doctorId, $date, $token, $request) {
            $queue = $this->lockQueue($clinic, $doctorId, $date);
            $queue->current_token = $token;
            $queue->updated_by = $request->user()->id;
            $queue->save();

            return $this->claimTokenReminders($clinic, $doctorId, $date, $token);
        });

        $this->sendTokenReminders($clinic, $reminders);

        return $this->success(
            $this->runningToken($clinic, $doctorId),
            $token > 0 ? 'Now serving token ' . $token . '.' : 'Token queue reset.'
        );
    }

    /**
     * POST /api/doctor/clinic-token/decrease with doctor_id (see staffDoctorId): move the
     * doctor's running token for today back to their previous booked token, or to 0
     * (not started) from the first one.
     */
    public function decrease(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $doctorId = $this->staffDoctorId($request, $clinic);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $date = $this->today();
        $reminders = [];

        $moved = DB::transaction(function () use ($clinic, $doctorId, $date, $request, &$reminders) {
            $queue = $this->lockQueue($clinic, $doctorId, $date);

            if ($queue->current_token === 0) {
                return false;
            }

            $previousToken = $this->activeTokens($clinic, $doctorId, $date)
                ->where('token_no', '<', $queue->current_token)
                ->max('token_no');

            $queue->current_token = (int) ($previousToken ?? 0);
            $queue->updated_by = $request->user()->id;
            $queue->save();

            $reminders = $this->claimTokenReminders($clinic, $doctorId, $date, $queue->current_token);

            return true;
        });

        $data = $this->runningToken($clinic, $doctorId);
        $this->sendTokenReminders($clinic, $reminders);

        $message = match (true) {
            ! $moved => 'The token queue has not started yet.',
            $data['running_token'] === 0 => 'Back to the start of the queue.',
            default => 'Now serving token ' . $data['running_token'] . '.',
        };

        return $this->success($data, $message);
    }

    /**
     * GET /api/doctor/clinic-patients with optional date (Y-m-d, default today) and doctor_id.
     * Patients holding a token with that doctor at the user's clinic that day, in token
     * order. Without doctor_id a doctor sees their own patients; an employee sees the
     * clinic's only doctor's patients, or every doctor's (with no single running token).
     */
    public function patients(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $date = $this->dateFrom($request);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        $doctorId = $this->staffDoctorId($request, $clinic, allowAll: true);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $appointments = ($doctorId ? $this->activeTokens($clinic, $doctorId, $date) : $this->clinicTokens($clinic, $date))
            ->with(['patient:id,name,lastname,phone', 'doctor:id,name,surname'])
            ->orderBy('dr_id')
            ->orderBy('token_no')
            ->get();

        $patients = $appointments->map(fn (Appointment $appointment) => [
            'appointment_id' => $appointment->id,
            'token_no' => (int) $appointment->token_no,
            'name' => trim(($appointment->patient?->name ?? '') . ' ' . ($appointment->patient?->lastname ?? '')),
            'mobile' => $appointment->patient?->phone,
            'is_present' => (bool) $appointment->is_present,
            // Token numbers repeat across doctors, so each row says whose token it is.
            'doctor_id' => (int) $appointment->dr_id,
            'doctor_name' => $appointment->doctor ? trim($appointment->doctor->name . ' ' . ($appointment->doctor->surname ?? '')) : null,
        ])->values();

        return $this->success([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'date' => $date,
            'running_token' => $doctorId ? $this->currentToken($clinic, $doctorId, $date) : null,
            'total_patients' => $patients->count(),
            'present_count' => $patients->where('is_present', true)->count(),
            'patients' => $patients,
        ]);
    }

    /**
     * POST /api/doctor/clinic-patients/attendance with appointment_id and is_present (1 or 0).
     * Marks whether a patient holding a token at the user's clinic has arrived.
     */
    public function attendance(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer',
            'is_present' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('appointment_id and is_present (1 or 0) are required.');
        }

        $appointment = $this->clinicTokens($clinic, null)
            ->whereKey($request->input('appointment_id'))
            ->first();

        if (! $appointment) {
            return $this->error('Appointment not found in your clinic.');
        }

        $appointment->is_present = $request->boolean('is_present') ? 1 : 0;
        $appointment->save();

        $date = $appointment->varAppointment;
        $doctorId = (int) $appointment->dr_id;

        return $this->success([
            'appointment_id' => $appointment->id,
            'token_no' => (int) $appointment->token_no,
            'doctor_id' => $doctorId,
            'date' => $date,
            'is_present' => (bool) $appointment->is_present,
            'total_patients' => $this->activeTokens($clinic, $doctorId, $date)->count(),
            'present_count' => $this->activeTokens($clinic, $doctorId, $date)->where('is_present', 1)->count(),
        ], $appointment->is_present ? 'Patient marked present.' : 'Patient marked not present.');
    }

    /** POST /api/doctor/clinic-token/set with token_no and doctor_id (see staffDoctorId): jump to or recall a token; 0 resets. */
    public function set(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $validator = Validator::make($request->all(), [
            'token_no' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $doctorId = $this->staffDoctorId($request, $clinic);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $date = $this->today();
        $token = (int) $request->input('token_no');

        if ($token > 0 && ! $this->activeTokens($clinic, $doctorId, $date)->where('token_no', $token)->exists()) {
            return $this->error('Token ' . $token . ' has not been issued for today.');
        }

        $reminders = DB::transaction(function () use ($clinic, $doctorId, $date, $token, $request) {
            $queue = $this->lockQueue($clinic, $doctorId, $date);
            $queue->current_token = $token;
            $queue->updated_by = $request->user()->id;
            $queue->save();

            return $this->claimTokenReminders($clinic, $doctorId, $date, $token);
        });

        $this->sendTokenReminders($clinic, $reminders);

        return $this->success(
            $this->runningToken($clinic, $doctorId),
            $token > 0 ? 'Now serving token ' . $token . '.' : 'Token queue reset.'
        );
    }

    /**
     * POST /api/doctor/clinic-token/clear with doctor_id (see staffDoctorId) and optional date
     * (Y-m-d, today or later; default today). Frees every token booked with that doctor at the
     * user's clinic for that date and resets the doctor's running token. The appointments stay
     * as they are; only their token numbers go.
     */
    public function clear(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->error('date must be today or a later date in Y-m-d format.');
        }

        $doctorId = $this->staffDoctorId($request, $clinic);
        if ($doctorId instanceof JsonResponse) {
            return $doctorId;
        }

        $date = $request->input('date') ?: $this->today();

        $cleared = DB::transaction(function () use ($clinic, $doctorId, $date, $request) {
            // Same lock as booking, so no token is handed out while clearing.
            Clinic::whereKey($clinic->id)->lockForUpdate()->first();

            $cleared = $this->activeTokens($clinic, $doctorId, $date)->update(['token_no' => null]);

            $queue = $this->lockQueue($clinic, $doctorId, $date);
            $queue->current_token = 0;
            $queue->updated_by = $request->user()->id;
            $queue->save();

            return $cleared;
        });

        return $this->success([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'date' => $date,
            'cleared_tokens' => $cleared,
            'running_token' => 0,
            'max_tokens' => $clinic->max_tokens,
        ], $cleared > 0
            ? 'Cleared ' . $cleared . ' booked ' . ($cleared === 1 ? 'token' : 'tokens') . '.'
            : 'No booked tokens to clear.');
    }

    private function runningToken(Clinic $clinic, int $doctorId, ?string $date = null): array
    {
        $date ??= $this->today();

        return [
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'date' => $date,
            'running_token' => $this->currentToken($clinic, $doctorId, $date),
            'max_tokens' => $clinic->max_tokens,
        ];
    }

    /** 0 until staff call the doctor's first token of the day. Cached briefly; writes clear it (see ClinicTokenQueue). */
    private function currentToken(Clinic $clinic, int $doctorId, string $date): int
    {
        return (int) Cache::remember(
            ClinicTokenQueue::runningTokenCacheKey($clinic->id, $doctorId, $date),
            self::RUNNING_TOKEN_TTL,
            fn () => (int) ClinicTokenQueue::where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctorId)
                ->where('queue_date', $date)
                ->value('current_token')
        );
    }

    /**
     * Shared checks for the read endpoints: a patient or doctor login, then a
     * clinic_id of an active clinic and a doctor_id of a doctor in it (staff default
     * to their own clinic, and to themselves or the clinic's only doctor).
     */
    private function readerContext(Request $request, bool $withDate = false): array|JsonResponse
    {
        // Patient token first: Passport clears the Authorization header when it
        // rejects a token, and it would reject every patient token.
        $patient = $this->patientFromToken($request);
        $staff = $patient ? null : Auth::guard('api')->user();

        if (! $patient && ! $staff) {
            return response()->json([
                'message' => 'Unauthorized request',
                'data' => [
                    'error' => 'Unauthorized request',
                ],
            ], 401);
        }

        // Patients must say which clinic and doctor; a doctor or employee defaults to
        // their own clinic and (for a doctor) their own id.
        $presence = $patient ? 'required' : 'nullable';
        $rules = [
            'clinic_id' => $presence . '|integer',
            'doctor_id' => $presence . '|integer',
        ];

        if ($withDate) {
            $rules['date'] = 'nullable|date_format:Y-m-d';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error(match (true) {
                $validator->errors()->has('date') => 'date must be in Y-m-d format.',
                $patient !== null => 'clinic_id and doctor_id are required.',
                default => 'clinic_id and doctor_id must be numbers.',
            });
        }

        $clinicGiven = $request->filled('clinic_id');
        $clinicId = $clinicGiven ? (int) $request->input('clinic_id') : $staff?->clinic_id;
        $clinic = $clinicId ? Clinic::where('is_active', true)->find($clinicId) : null;

        if (! $clinic) {
            return $this->error($clinicGiven ? 'Clinic not found.' : 'You are not linked to an active clinic.');
        }

        $doctorId = match (true) {
            $request->filled('doctor_id') => (int) $request->input('doctor_id'),
            ! $staff->isEmployee() => (int) $staff->id,
            default => $this->onlyDoctorId($clinic),
        };

        if ($doctorId === null) {
            return $this->error('doctor_id is required.');
        }

        if (! $this->isClinicDoctor($clinic, $doctorId)) {
            return $this->error('This doctor is not part of the selected clinic.');
        }

        return [
            'clinic' => $clinic,
            'doctor_id' => $doctorId,
            'patient' => $patient,
            'date' => $request->input('date') ?: $this->today(),
        ];
    }

    /**
     * The doctor's booked rows that day, their token numbers (as keys) and how many
     * tokens the board has. Without a daily max it is what's booked plus one free
     * token; if the max was lowered after booking, tokens already handed out still count.
     */
    private function tokenBoard(Clinic $clinic, int $doctorId, string $date): array
    {
        $booked = $this->activeTokens($clinic, $doctorId, $date)->get(['token_no', 'patient_id']);
        $bookedTokens = $booked->pluck('token_no')->map(fn ($token) => (int) $token)->unique()->flip();
        $highestBooked = $bookedTokens->keys()->max() ?? 0;
        $total = max($clinic->max_tokens ?: $highestBooked + 1, $highestBooked);

        return [$booked, $bookedTokens, $total];
    }

    /** A doctor's tokens still in the queue that day: cancelled and rejected requests are skipped. */
    private function activeTokens(Clinic $clinic, int $doctorId, string $date)
    {
        return $this->clinicTokens($clinic, $date)->where('dr_id', $doctorId);
    }

    /** Every doctor's tokens still in the queue at the clinic (that day, or any day for null). */
    private function clinicTokens(Clinic $clinic, ?string $date)
    {
        return Appointment::where('clinic_id', $clinic->id)
            ->when($date !== null, fn ($query) => $query->where('varAppointment', $date))
            ->where('chrIsCanceled', 'N')
            ->where('chrIsRejected', 'N')
            ->whereNotNull('token_no');
    }

    /** Creates the doctor's queue row for the day if needed and locks it; call inside a transaction. */
    private function lockQueue(Clinic $clinic, int $doctorId, string $date): ClinicTokenQueue
    {
        DB::table('clinic_token_queues')->insertOrIgnore([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'queue_date' => $date,
            'current_token' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ClinicTokenQueue::where('clinic_id', $clinic->id)
            ->where('doctor_id', $doctorId)
            ->where('queue_date', $date)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Marks the doctor's bookings now within REMINDER_POSITIONS places after the running
     * token as reminded and returns them with their place in line (1 = next). Every place
     * is checked, not just the last one, so a late booking, a cancellation or a jump in
     * the token still gets everyone in range told; each booking is reminded only once,
     * however often the token moves back and forth. Call inside the queue transaction.
     */
    private function claimTokenReminders(Clinic $clinic, int $doctorId, string $date, int $runningToken): array
    {
        if ($runningToken === 0) {
            return [];
        }

        $upcoming = $this->activeTokens($clinic, $doctorId, $date)
            ->where('token_no', '>', $runningToken)
            ->orderBy('token_no')
            ->limit(self::REMINDER_POSITIONS)
            ->get(['id', 'token_reminder_sent_at']);

        $places = [];
        foreach ($upcoming->values() as $index => $appointment) {
            if ($appointment->token_reminder_sent_at === null) {
                $places[$appointment->id] = $index + 1;
            }
        }

        if (! $places) {
            return [];
        }

        Appointment::whereIn('id', array_keys($places))->update(['token_reminder_sent_at' => now()]);

        return ['doctor_id' => $doctorId, 'running_token' => $runningToken, 'places' => $places];
    }

    /** Pushes the claimed reminders after the response is sent, so FCM never slows the doctor's tap. */
    private function sendTokenReminders(Clinic $clinic, array $reminders): void
    {
        if (! $reminders) {
            return;
        }

        app()->terminating(function () use ($clinic, $reminders) {
            $runningToken = $reminders['running_token'];
            $doctor = User::find($reminders['doctor_id'], ['id', 'name', 'surname']);
            $appointments = Appointment::with('patient:id,fcm_token')
                ->whereIn('id', array_keys($reminders['places']))
                ->get(['id', 'patient_id', 'token_no']);
            $notifications = new NotificationController();
            $serving = 'Now serving token ' . $runningToken
                . ($doctor ? ' for Dr. ' . trim($doctor->name . ' ' . ($doctor->surname ?? '')) : '')
                . ' at ' . $clinic->name . '. ';

            foreach ($appointments as $appointment) {
                if (empty($appointment->patient?->fcm_token)) {
                    continue;
                }

                $ahead = $reminders['places'][$appointment->id] - 1;

                $notifications->sendPushNotification(
                    $appointment->patient->fcm_token,
                    $ahead === 0 ? "You're next!" : 'Your turn is coming up',
                    $ahead === 0
                        ? $serving . 'Your token ' . $appointment->token_no . ' is next, please be at the clinic.'
                        : $serving . 'Your token ' . $appointment->token_no . ' is coming up with ' . $ahead
                            . ($ahead === 1 ? ' patient' : ' patients') . ' ahead of you. Please be available.',
                    'patient',
                    [
                        'type' => 'clinic_token_reminder',
                        'appointmentId' => $appointment->id,
                        'clinicId' => $clinic->id,
                        'doctorId' => $reminders['doctor_id'],
                        'tokenNo' => $appointment->token_no,
                        'runningToken' => $runningToken,
                        'patientsAhead' => $ahead,
                    ],
                    'clinic_token_reminder'
                );
            }
        });
    }

    /** The logged-in patient, using the same check as the AuthenticateToken middleware. */
    private function patientFromToken(Request $request): ?Patients
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        try {
            $payload = decrypt($token);
        } catch (DecryptException) {
            return null;
        }

        $patient = is_array($payload) && ! empty($payload['id']) ? Patients::find($payload['id']) : null;

        return $patient && $patient->remember_token === $token ? $patient : null;
    }

    /** The active clinic of the logged-in doctor or employee, or an error response. */
    private function staffClinic(Request $request): Clinic|JsonResponse
    {
        $user = $request->user();

        if ($user->chrApproval !== 'Y') {
            return response()->json([
                'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status' => 200,
            ], 200);
        }

        $clinic = $user->clinic_id ? Clinic::where('is_active', true)->find($user->clinic_id) : null;

        return $clinic ?: $this->error('You are not linked to an active clinic.');
    }

    /**
     * Whose token queue a staff call works on: the posted doctor_id (a doctor of the
     * clinic), else the logged-in doctor, else the clinic's only doctor. An employee of
     * a clinic with several doctors must send doctor_id, unless $allowAll lets null
     * stand for "every doctor".
     */
    private function staffDoctorId(Request $request, Clinic $clinic, bool $allowAll = false): int|JsonResponse|null
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->error('doctor_id must be a number.');
        }

        if ($request->filled('doctor_id')) {
            $doctorId = (int) $request->input('doctor_id');

            return $this->isClinicDoctor($clinic, $doctorId) ? $doctorId : $this->error('This doctor is not part of your clinic.');
        }

        if (! $request->user()->isEmployee()) {
            return (int) $request->user()->id;
        }

        $doctorId = $this->onlyDoctorId($clinic);

        return $doctorId ?? ($allowAll ? null : $this->error('doctor_id is required.'));
    }

    /** The clinic's doctor when it has exactly one approved doctor, else null. */
    private function onlyDoctorId(Clinic $clinic): ?int
    {
        $doctorIds = User::doctors()
            ->where('clinic_id', $clinic->id)
            ->where('chrApproval', 'Y')
            ->limit(2)
            ->pluck('id');

        return $doctorIds->count() === 1 ? (int) $doctorIds->first() : null;
    }

    private function isClinicDoctor(Clinic $clinic, int $doctorId): bool
    {
        return User::doctors()->whereKey($doctorId)->where('clinic_id', $clinic->id)->exists();
    }

    private function today(): string
    {
        return Carbon::now()->toDateString();
    }

    /** The optional `date` param (Y-m-d), defaulting to today. */
    private function dateFrom(Request $request): string|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->error('date must be in Y-m-d format.');
        }

        return $request->input('date') ?: $this->today();
    }

    private function success(array $data, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    private function error(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [
                'error' => $message,
            ],
        ], 400);
    }
}
