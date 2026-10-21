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
        // Patient token first: Passport clears the Authorization header when it
        // rejects a token, and it would reject every patient token.
        if (! $this->hasValidPatientToken($request) && ! Auth::guard('api')->check()) {
            return response()->json([
                'message' => 'Unauthorized request',
                'data' => [
                    'error' => 'Unauthorized request',
                ],
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|integer',
            'doctor_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->error('clinic_id and doctor_id are required.');
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

        return $this->success($this->runningToken($clinic, $doctorId));
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

    private function runningToken(Clinic $clinic, ?int $doctorId): array
    {
        $date = $this->today();

        return [
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctorId,
            'date' => $date,
            // 0 until staff call the first token of the day.
            'running_token' => (int) ClinicTokenQueue::where('clinic_id', $clinic->id)
                ->where('queue_date', $date)
                ->value('current_token'),
            'max_tokens' => $clinic->max_tokens,
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

    /** Same check as the AuthenticateToken middleware used on patient routes. */
    private function hasValidPatientToken(Request $request): bool
    {
        $token = $request->bearerToken();

        if (! $token) {
            return false;
        }

        try {
            $payload = decrypt($token);
        } catch (DecryptException) {
            return false;
        }

        $patient = is_array($payload) && ! empty($payload['id']) ? Patients::find($payload['id']) : null;

        return $patient !== null && $patient->remember_token === $token;
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
