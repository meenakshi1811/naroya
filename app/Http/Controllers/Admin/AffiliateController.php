<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Appointment;
use App\Models\GeneralSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $period = $this->resolvePeriodSelection($request);
        $defaultCommissionRate = $this->defaultCommissionRate();

        $affiliatesQuery = Affiliate::with('doctor:id,name,surname,email')
            ->where('is_active', true)
            ->orderBy('name');

        if ($search !== '') {
            $affiliatesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhereHas('doctor', function ($doctorQuery) use ($search) {
                        $doctorQuery->where('email', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhere('surname', 'like', '%' . $search . '%');
                    });
            });
        }

        $affiliates = $affiliatesQuery->get();

        $affiliateStats = [];
        $totalBookings = 0;
        $totalValue = 0.0;
        $totalCommission = 0.0;
        $totalUsers = 0;

        foreach ($affiliates as $affiliate) {
            $stats = $this->statsForAffiliate(
                $affiliate,
                $defaultCommissionRate,
                $period['monthStart'],
                $period['monthEnd']
            );
            $affiliateStats[] = array_merge(['affiliate' => $affiliate], $stats);
            $totalBookings += $stats['bookings'];
            $totalValue += $stats['total_value'];
            $totalCommission += $stats['commission_owed'];
            $totalUsers += $stats['users'];
        }

        $approvedDoctors = User::where('chrApproval', 'Y')
            ->orderBy('name')
            ->get(['id', 'name', 'surname', 'email']);

        $existingDoctorIds = Affiliate::whereNotNull('doctor_id')->pluck('doctor_id')->all();

        return view('admin.affiliates.index', [
            'affiliateStats' => $affiliateStats,
            'activeAffiliates' => $affiliates->count(),
            'totalBookings' => $totalBookings,
            'totalValue' => $totalValue,
            'totalCommission' => $totalCommission,
            'totalUsers' => $totalUsers,
            'search' => $search,
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'isAllTime' => $period['isAllTime'],
            'monthOptions' => $this->buildMonthOptionsForYear($period['selectedYear']),
            'yearOptions' => $this->yearOptions(),
            'approvedDoctors' => $approvedDoctors,
            'existingDoctorIds' => $existingDoctorIds,
            'defaultCommissionRate' => $defaultCommissionRate,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAffiliate($request);

        $name = $validated['name'];
        if ($validated['source_type'] === 'existing_doctor' && ! empty($validated['doctor_id'])) {
            $doctor = User::findOrFail($validated['doctor_id']);
            $name = trim($doctor->name . ' ' . ($doctor->surname ?? ''));
        }

        $code = Affiliate::generateUniqueCode($validated['code'] ?? null);

        Affiliate::create([
            'doctor_id' => $validated['source_type'] === 'existing_doctor' ? $validated['doctor_id'] : null,
            'name' => $name,
            'code' => $code,
            'commission_rate' => $validated['commission_rate'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.affiliate', $this->filterQueryParams($request))
            ->with('success', 'Affiliate added successfully. QR code is ready to share.');
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $this->validateAffiliate($request, $affiliate->id);

        $name = $validated['name'];
        if ($validated['source_type'] === 'existing_doctor' && ! empty($validated['doctor_id'])) {
            $doctor = User::findOrFail($validated['doctor_id']);
            $name = trim($doctor->name . ' ' . ($doctor->surname ?? ''));
        }

        $code = $affiliate->code;
        if (! empty($validated['code'])) {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $validated['code']));
        }

        $affiliate->update([
            'doctor_id' => $validated['source_type'] === 'existing_doctor' ? $validated['doctor_id'] : null,
            'name' => $name,
            'code' => $code,
            'commission_rate' => $validated['commission_rate'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.affiliate', $this->filterQueryParams($request))
            ->with('success', 'Affiliate updated successfully.');
    }

    public function destroy(Request $request, Affiliate $affiliate)
    {
        $affiliate->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Affiliate removed successfully.']);
        }

        return redirect()
            ->route('admin.affiliate')
            ->with('success', 'Affiliate removed successfully.');
    }

    public function generateCode(Request $request)
    {
        $preferred = $request->input('preferred');
        $excludeId = $request->input('exclude_id');

        $code = Affiliate::generateUniqueCode($preferred);

        if ($excludeId && Affiliate::where('code', $code)->where('id', '!=', $excludeId)->exists()) {
            $code = Affiliate::generateUniqueCode(null);
        }

        return response()->json(['code' => $code]);
    }

    public function updateDefaultCommission(Request $request)
    {
        $validated = $request->validate([
            'affiliate_commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        GeneralSetting::updateOrCreate(
            ['field_name' => 'affiliate_commission_percentage'],
            ['field_value' => $validated['affiliate_commission_percentage']]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Default commission rate updated.',
                'rate' => (float) $validated['affiliate_commission_percentage'],
            ]);
        }

        return redirect()
            ->route('admin.affiliate', $this->filterQueryParams($request))
            ->with('success', 'Default commission rate updated.');
    }

    private function validateAffiliate(Request $request, ?int $affiliateId = null): array
    {
        $rules = [
            'source_type' => ['required', Rule::in(['existing_doctor', 'custom'])],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'name' => ['required_if:source_type,custom', 'nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('affiliates', 'code')->ignore($affiliateId),
            ],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        $validated = $request->validate($rules);

        if ($validated['source_type'] === 'existing_doctor') {
            $request->validate(['doctor_id' => ['required', 'exists:users,id']]);
            $validated['doctor_id'] = $request->doctor_id;

            $doctorQuery = Affiliate::where('doctor_id', $validated['doctor_id']);
            if ($affiliateId) {
                $doctorQuery->where('id', '!=', $affiliateId);
            }
            if ($doctorQuery->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'doctor_id' => 'This doctor is already registered as an affiliate.',
                ]);
            }
        }

        return $validated;
    }

    private function statsForAffiliate(
        Affiliate $affiliate,
        float $defaultRate,
        ?Carbon $monthStart = null,
        ?Carbon $monthEnd = null
    ): array {
        $usersQuery = $affiliate->patients();

        if ($monthStart && $monthEnd) {
            $usersQuery->whereBetween('created_at', [$monthStart, $monthEnd]);
        }

        $users = (int) $usersQuery->count();
        $patientIds = $affiliate->patients()->pluck('id');

        $bookingQuery = Appointment::query()
            ->whereIn('patient_id', $patientIds)
            ->where('charIsPaid', 'Y')
            ->where('chrIsCanceled', 'N');

        if ($monthStart && $monthEnd) {
            $bookingQuery->whereBetween('varAppointment', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ]);
        }

        $bookings = (clone $bookingQuery)->count();
        $totalValue = (float) (clone $bookingQuery)->sum(DB::raw('COALESCE(amount, 0)'));
        $rate = $affiliate->commission_rate ?? $defaultRate;
        $commissionOwed = round($totalValue * ($rate / 100), 2);

        return [
            'users' => $users,
            'bookings' => $bookings,
            'total_value' => $totalValue,
            'commission_rate' => $rate,
            'commission_owed' => $commissionOwed,
        ];
    }

    private function defaultCommissionRate(): float
    {
        $setting = GeneralSetting::where('field_name', 'affiliate_commission_percentage')->value('field_value');

        if ($setting !== null && $setting !== '') {
            return (float) $setting;
        }

        return 3.0;
    }

    private function resolvePeriodSelection(Request $request): array
    {
        $monthInput = $request->input('month', (string) now()->month);
        $now = Carbon::now();
        $currentYear = (int) $now->year;
        $currentMonth = (int) $now->month;

        if ($monthInput === 'all') {
            return [
                'selectedMonth' => 'all',
                'selectedYear' => $currentYear,
                'isAllTime' => true,
                'monthStart' => null,
                'monthEnd' => null,
            ];
        }

        $selectedYear = (int) $request->input('year', $currentYear);
        $yearStart = max(2026, $currentYear - 2);

        if ($selectedYear < $yearStart || $selectedYear > $currentYear) {
            $selectedYear = $currentYear;
        }

        $selectedMonth = (int) $monthInput;
        $maxMonth = $selectedYear === $currentYear ? $currentMonth : 12;

        if ($selectedMonth < 1 || $selectedMonth > $maxMonth) {
            $selectedMonth = $maxMonth;
        }

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        return [
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'isAllTime' => false,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
        ];
    }

    private function buildMonthOptionsForYear(int $selectedYear): array
    {
        $now = Carbon::now();
        $currentYear = (int) $now->year;
        $currentMonth = (int) $now->month;
        $maxMonth = $selectedYear === $currentYear ? $currentMonth : 12;

        $options = ['all' => 'All'];

        for ($month = 1; $month <= $maxMonth; $month++) {
            $options[$month] = Carbon::create(2000, $month, 1)->format('F');
        }

        return $options;
    }

    private function yearOptions(): array
    {
        $currentYear = (int) now()->year;
        $yearStart = max(2026, $currentYear - 2);

        return range($yearStart, $currentYear);
    }

    private function filterQueryParams(Request $request): array
    {
        return array_filter([
            'month' => $request->input('month'),
            'year' => $request->input('year'),
            'search' => trim((string) $request->input('search', '')),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
