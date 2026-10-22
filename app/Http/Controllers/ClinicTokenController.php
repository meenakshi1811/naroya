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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Running ("now serving") token of a clinic's daily queue. Tokens are issued per
 * clinic per date when a patient sends a booking request; clinic staff move the
 * running token forward and both apps read it.
 */
class ClinicTokenController extends Controller
{
    /**
     * GET|POST /api/clinic-running-token with clinic_id and doctor_id.
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
     * GET|POST /api/clinic-tokens with clinic_id, doctor_id and optional date (Y-m-d, default today).
     * Token map like a cinema seat plan: every token 1..max_tokens with its status.
     * Works with either a patient login token or a doctor login token.
     */
    public function tokens(Request $request): JsonResponse
    {
        $context = $this->readerContext($request, withDate: true);
        if ($context instanceof JsonResponse) {
            return $context;
        }

        $clinic = $context['clinic'];
        $date = $context['date'];
        $patient = $context['patient'];

        $booked = $this->activeTokens($clinic, $date)->get(['token_no', 'patient_id']);
        $bookedTokens = $booked->pluck('token_no')->map(fn ($token) => (int) $token)->unique()->flip();
        $myTokens = $patient
            ? $booked->where('patient_id', $patient->id)->pluck('token_no')->map(fn ($token) => (int) $token)->flip()
            : collect();

        // Without a daily max, show what's booked plus one free token. If the max
        // was lowered after booking, still show the tokens already handed out.
        $highestBooked = $bookedTokens->keys()->max() ?? 0;
        $total = max($clinic->max_tokens ?: $highestBooked + 1, $highestBooked);

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
            'doctor_id' => $context['doctor_id'],
            'date' => $date,
            'max_tokens' => $clinic->max_tokens,
            'total_tokens' => $total,
            'booked_count' => $bookedTokens->count(),
            'available_count' => $total - $bookedTokens->count(),
            'running_token' => $this->currentToken($clinic, $date),
            'tokens' => $tokens,
        ]);
    }

    /** POST /api/doctor/clinic-token/next: serve the next waiting token at the user's clinic. */
    public function next(Request $request): JsonResponse
    {
        $clinic = $this->staffClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $date = $this->today();

        $advanced = DB::transaction(function () use ($clinic, $date, $request) {
            $queue = $this->lockQueue($clinic, $date);

            $nextToken = $this->activeTokens($clinic, $date)
                ->where('token_no', '>', $queue->current_token)
                ->min('token_no');

            if ($nextToken === null) {
                return false;
            }

            $queue->current_token = (int) $nextToken;
            $queue->updated_by = $request->user()->id;
            $queue->save();

            return true;
        });

        $data = $this->runningToken($clinic, $this->staffDoctorId($request));

        return $this->success(
            $data,
            $advanced ? 'Now serving token ' . $data['running_token'] . '.' : 'No more tokens are waiting.'
        );
    }

    /** POST /api/doctor/clinic-token/set with token_no: jump to or recall a token; 0 resets. */
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

        $date = $this->today();
        $token = (int) $request->input('token_no');

        if ($token > 0 && ! $this->activeTokens($clinic, $date)->where('token_no', $token)->exists()) {
            return $this->error('Token ' . $token . ' has not been issued for today.');
        }

        DB::transaction(function () use ($clinic, $date, $token, $request) {
            $queue = $this->lockQueue($clinic, $date);
            $queue->current_token = $token;
            $queue->updated_by = $request->user()->id;
            $queue->save();
        });

        return $this->success(
            $this->runningToken($clinic, $this->staffDoctorId($request)),
            $token > 0 ? 'Now serving token ' . $token . '.' : 'Token queue reset.'
        );
    }

    /**
     * POST /api/doctor/clinic-token/clear with optional date (Y-m-d, today or later; default today).
     * Frees every booked token at the user's clinic for that date and resets the
     * running token. The appointments stay as they are; only their token numbers go.
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

        $date = $request->input('date') ?: $this->today();

        $cleared = DB::transaction(function () use ($clinic, $date, $request) {
            // Same lock as booking, so no token is handed out while clearing.
            Clinic::whereKey($clinic->id)->lockForUpdate()->first();

            $cleared = $this->activeTokens($clinic, $date)->update(['token_no' => null]);

            $queue = $this->lockQueue($clinic, $date);
            $queue->current_token = 0;
            $queue->updated_by = $request->user()->id;
            $queue->save();

            return $cleared;
        });

        return $this->success([
            'clinic_id' => $clinic->id,
            'date' => $date,
            'cleared_tokens' => $cleared,
            'running_token' => 0,
            'max_tokens' => $clinic->max_tokens,
        ], $cleared > 0
            ? 'Cleared ' . $cleared . ' booked ' . ($cleared === 1 ? 'token' : 'tokens') . '.'
            : 'No booked tokens to clear.');
    }

    private function runningToken(Clinic $clinic, ?int $doctorId): array
    {
        $date = $this->today();

        return [
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'date' => $date,
            'running_token' => $this->currentToken($clinic, $date),
            'max_tokens' => $clinic->max_tokens,
        ];
    }

    /** 0 until staff call the first token of the day. */
    private function currentToken(Clinic $clinic, string $date): int
    {
        return (int) ClinicTokenQueue::where('clinic_id', $clinic->id)
            ->where('queue_date', $date)
            ->value('current_token');
    }

    /**
     * Shared checks for the read endpoints: a patient or doctor login, then a
     * clinic_id of an active clinic and a doctor_id of a doctor in it.
     */
    private function readerContext(Request $request, bool $withDate = false): array|JsonResponse
    {
        // Patient token first: Passport clears the Authorization header when it
        // rejects a token, and it would reject every patient token.
        $patient = $this->patientFromToken($request);

        if (! $patient && ! Auth::guard('api')->check()) {
            return response()->json([
                'message' => 'Unauthorized request',
                'data' => [
                    'error' => 'Unauthorized request',
                ],
            ], 401);
        }

        $rules = [
            'clinic_id' => 'required|integer',
            'doctor_id' => 'required|integer',
        ];

        if ($withDate) {
            $rules['date'] = 'nullable|date_format:Y-m-d';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error($validator->errors()->has('date')
                ? 'date must be in Y-m-d format.'
                : 'clinic_id and doctor_id are required.');
        }

        $clinic = Clinic::where('is_active', true)->find($request->input('clinic_id'));

        if (! $clinic) {
            return $this->error('Clinic not found.');
        }

        $doctorId = (int) $request->input('doctor_id');
        $doctorInClinic = User::doctors()
            ->whereKey($doctorId)
            ->where('clinic_id', $clinic->id)
            ->exists();

        if (! $doctorInClinic) {
            return $this->error('This doctor is not part of the selected clinic.');
        }

        return [
            'clinic' => $clinic,
            'doctor_id' => $doctorId,
            'patient' => $patient,
            'date' => $request->input('date') ?: $this->today(),
        ];
    }

    /** Tokens still in the queue: cancelled and rejected requests are skipped. */
    private function activeTokens(Clinic $clinic, string $date)
    {
        return Appointment::where('clinic_id', $clinic->id)
            ->where('varAppointment', $date)
            ->where('chrIsCanceled', 'N')
            ->where('chrIsRejected', 'N')
            ->whereNotNull('token_no');
    }

    /** Creates the day's queue row if needed and locks it; call inside a transaction. */
    private function lockQueue(Clinic $clinic, string $date): ClinicTokenQueue
    {
        DB::table('clinic_token_queues')->insertOrIgnore([
            'clinic_id' => $clinic->id,
            'queue_date' => $date,
            'current_token' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ClinicTokenQueue::where('clinic_id', $clinic->id)
            ->where('queue_date', $date)
            ->lockForUpdate()
            ->first();
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

    private function staffDoctorId(Request $request): ?int
    {
        return $request->user()->isEmployee() ? null : $request->user()->id;
    }

    private function today(): string
    {
        return Carbon::now()->toDateString();
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
