<?php

namespace App\Http\Controllers;

use App\Mail\ClinicEmployeeCredentialsMail;
use App\Models\Clinic;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Passport\RefreshToken;

/**
 * Doctor-app APIs for clinic staff (doctor login token). A clinic's doctor manages
 * its employees; any doctor or employee can read their own clinic.
 */
class ClinicStaffController extends Controller
{
    /** GET /api/my-clinic: the logged-in doctor's or employee's clinic. */
    public function clinic(Request $request): JsonResponse
    {
        $clinic = $this->ownClinic($request, ['stateRel:id,name', 'cityRel:id,name', 'countryRel:id,countryname']);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $doctors = $this->clinicDoctors($clinic);

        return $this->success([
            'role' => $request->user()->role,
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'image' => $clinic->logo_url,
                'registration_no' => $clinic->registration_no,
                'email' => $clinic->email,
                'phone' => $clinic->phone,
                'website' => $clinic->website,
                'address' => $clinic->full_address,
                'city_name' => $clinic->cityRel?->name,
                'state_name' => $clinic->stateRel?->name,
                'description' => $clinic->description,
                'clinic_time' => $clinic->timing_labels,
                'timings' => $clinic->timings ?? [],
                'max_tokens' => $clinic->max_tokens,
            ],
            'doctors' => $doctors,
            'employees_count' => $clinic->employees()->count(),
        ]);
    }

    /**
     * GET /api/my-clinic/doctors: the doctors of the logged-in employee's (or doctor's)
     * clinic, e.g. to pick one and pass its id as doctor_id to /doctor/clinic-patients.
     */
    public function doctors(Request $request): JsonResponse
    {
        $clinic = $this->ownClinic($request);
        if ($clinic instanceof JsonResponse) {
            return $clinic;
        }

        $doctors = $this->clinicDoctors($clinic);

        return $this->success([
            'clinic_id' => $clinic->id,
            'total' => $doctors->count(),
            'doctors' => $doctors,
        ]);
    }

    /** GET /api/doctor/employees: employees of the doctor's clinic. */
    public function index(Request $request): JsonResponse
    {
        $owner = $this->clinicDoctor($request);
        if ($owner instanceof JsonResponse) {
            return $owner;
        }

        [, $clinic] = $owner;

        $employees = $clinic->employees()
            ->orderBy('name')
            ->get()
            ->map(fn (User $employee) => $this->employeeData($employee))
            ->values();

        return $this->success([
            'clinic_id' => $clinic->id,
            'total' => $employees->count(),
            'employees' => $employees,
        ]);
    }

    /**
     * POST /api/doctor/employees with first_name, email, password and optional surname, phone.
     * Creates an approved employee login in the doctor's clinic and emails the credentials.
     */
    public function store(Request $request): JsonResponse
    {
        $owner = $this->clinicDoctor($request);
        if ($owner instanceof JsonResponse) {
            return $owner;
        }

        [$doctor, $clinic] = $owner;

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6|max:100',
        ], [
            'email.unique' => 'This email is already registered.',
        ], [
            'first_name' => 'first name',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $employee = new User();
        $employee->role = User::ROLE_EMPLOYEE;
        $employee->clinic_id = $clinic->id;
        $employee->chrApproval = 'Y';
        $employee->name = trim($request->input('first_name'));
        $employee->email = trim($request->input('email'));
        $employee->password = Hash::make($request->input('password'));
        $employee->varTimeDuration = GeneralSetting::where('field_name', 'time_duration')->value('field_value');

        // Only set optional fields that were sent, so column defaults apply otherwise.
        if ($request->filled('surname')) {
            $employee->surname = trim($request->input('surname'));
        }

        if ($request->filled('phone')) {
            $employee->phoneNumber = trim($request->input('phone'));
        }

        $employee->save();

        $emailSent = $this->sendCredentials($employee, $clinic, $doctor, $request->input('password'));

        return $this->success([
            'employee' => $this->employeeData($employee->fresh()),
            'email_sent' => $emailSent,
        ], $emailSent
            ? 'Employee created. Login details were emailed to ' . $employee->email . '.'
            : 'Employee created, but the login email could not be sent.');
    }

    /**
     * POST|PUT /api/doctor/employees/{id} with any of first_name, surname, email, phone, password.
     * Fields left out stay as they are.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $owner = $this->clinicDoctor($request);
        if ($owner instanceof JsonResponse) {
            return $owner;
        }

        [, $clinic] = $owner;

        $employee = $this->findEmployee($clinic, $id);
        if (! $employee) {
            return $this->error('Employee not found.');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'surname' => 'sometimes|nullable|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'phone' => 'sometimes|nullable|string|max:30',
            'password' => 'sometimes|nullable|string|min:6|max:100',
        ], [
            'email.unique' => 'This email is already registered.',
        ], [
            'first_name' => 'first name',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        if ($request->has('first_name')) {
            $employee->name = trim($request->input('first_name'));
        }

        if ($request->has('surname')) {
            $employee->surname = $request->filled('surname') ? trim($request->input('surname')) : null;
        }

        if ($request->has('email')) {
            $employee->email = trim($request->input('email'));
        }

        if ($request->has('phone')) {
            $employee->phoneNumber = $request->filled('phone') ? trim($request->input('phone')) : null;
        }

        if ($request->filled('password')) {
            $employee->password = Hash::make($request->input('password'));
        }

        $employee->save();

        return $this->success([
            'employee' => $this->employeeData($employee->fresh()),
        ], 'Employee updated successfully.');
    }

    /** DELETE /api/doctor/employees/{id}: deletes the employee's login and signs them out. */
    public function destroy(Request $request, $id): JsonResponse
    {
        $owner = $this->clinicDoctor($request);
        if ($owner instanceof JsonResponse) {
            return $owner;
        }

        [, $clinic] = $owner;

        $employee = $this->findEmployee($clinic, $id);
        if (! $employee) {
            return $this->error('Employee not found.');
        }

        // End any app sessions before the account goes away.
        $tokenIds = $employee->tokens()->pluck('id');
        RefreshToken::whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);
        $employee->tokens()->update(['revoked' => true]);

        $employee->delete();

        return $this->success([
            'employee_id' => (int) $id,
        ], 'Employee deleted successfully.');
    }

    /** The logged-in doctor's or employee's active clinic, or an error response. */
    private function ownClinic(Request $request, array $with = []): Clinic|JsonResponse
    {
        $user = $request->user();

        if ($user->chrApproval !== 'Y') {
            return $this->pendingApproval();
        }

        $clinic = $user->clinic_id
            ? Clinic::where('is_active', true)->with($with)->find($user->clinic_id)
            : null;

        return $clinic ?: $this->error('You are not linked to an active clinic.');
    }

    /** The clinic's approved doctors, by name. */
    private function clinicDoctors(Clinic $clinic)
    {
        return $clinic->doctors()
            ->where('chrApproval', 'Y')
            ->with('categoryRel:id,title')
            ->orderBy('name')
            ->get(['id', 'clinic_id', 'name', 'surname', 'category', 'varProfile', 'varGraduation', 'varPostGraduation'])
            ->map(fn (User $doctor) => [
                'id' => $doctor->id,
                'full_name' => $this->fullName($doctor),
                'speciality' => $doctor->categoryRel?->title,
                'degree' => collect([$doctor->varGraduation, $doctor->varPostGraduation])->filter()->implode(', '),
                'image' => $doctor->varProfile ? asset('api/docterprofile/' . $doctor->varProfile) : null,
            ])
            ->values();
    }

    /** The logged-in doctor and their active clinic, or an error response. */
    private function clinicDoctor(Request $request): array|JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployee()) {
            return $this->error('Only the clinic\'s doctors can manage employees.', 403);
        }

        if ($user->chrApproval !== 'Y') {
            return $this->pendingApproval();
        }

        $clinic = $user->clinic_id ? Clinic::where('is_active', true)->find($user->clinic_id) : null;

        if (! $clinic) {
            return $this->error('You are not linked to an active clinic.');
        }

        return [$user, $clinic];
    }

    private function findEmployee(Clinic $clinic, $id): ?User
    {
        if (! is_numeric($id)) {
            return null;
        }

        return $clinic->employees()->whereKey((int) $id)->first();
    }

    private function employeeData(User $employee): array
    {
        return [
            'id' => $employee->id,
            'first_name' => $employee->name,
            'surname' => $employee->surname,
            'full_name' => $this->fullName($employee),
            'email' => $employee->email,
            'phone' => $employee->phoneNumber,
            'role' => $employee->role,
            'clinic_id' => (int) $employee->clinic_id,
            'created_at' => optional($employee->created_at)->toDateTimeString(),
        ];
    }

    private function sendCredentials(User $employee, Clinic $clinic, User $doctor, string $password): bool
    {
        $clinic->loadMissing(['stateRel:id,name', 'cityRel:id,name', 'countryRel:id,countryname']);

        try {
            Mail::to($employee->email)->send(new ClinicEmployeeCredentialsMail(
                $this->fullName($employee),
                $clinic->name,
                $clinic->full_address ?: null,
                'Dr. ' . $this->fullName($doctor),
                $employee->email,
                $password
            ));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    private function fullName(User $user): string
    {
        return trim($user->name . ' ' . ($user->surname ?? ''));
    }

    private function pendingApproval(): JsonResponse
    {
        return response()->json([
            'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
            'status' => 200,
        ], 200);
    }

    private function success(array $data, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
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
