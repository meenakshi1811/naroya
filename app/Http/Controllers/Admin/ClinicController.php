<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ProvidesLocationOptions;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicTokenQueue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClinicController extends Controller
{
    use ProvidesLocationOptions;

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $clinics = Clinic::query()
            ->with(['cityRel:id,name', 'stateRel:id,name'])
            ->withCount(['doctors', 'employees'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('registration_no', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Clinic::count(),
            'active' => Clinic::where('is_active', true)->count(),
            'doctors' => User::doctors()->whereNotNull('clinic_id')->count(),
            'employees' => User::where('role', User::ROLE_EMPLOYEE)->whereNotNull('clinic_id')->count(),
        ];

        return view('admin.clinics.index', compact('clinics', 'stats', 'search', 'status'));
    }

    public function create()
    {
        $clinic = new Clinic(['is_active' => true]);

        return view('admin.clinics.form', array_merge(
            ['clinic' => $clinic],
            $this->locationOptions(old('state'))
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateClinic($request);

        $clinic = new Clinic(Arr::except($validated, ['logo']));
        $clinic->is_active = $request->boolean('is_active');

        if ($request->hasFile('logo')) {
            $clinic->logo = $this->storeLogo($request->file('logo'));
        }

        $clinic->save();

        return redirect()
            ->route('admin.clinic.show', $clinic)
            ->with('success', 'Clinic profile created. You can now add doctors and employees to it.');
    }

    public function show(Clinic $clinic)
    {
        $clinic->load(['countryRel:id,countryname', 'stateRel:id,name', 'cityRel:id,name']);

        $members = $clinic->members()
            ->with('categoryRel:id,title')
            ->orderBy('role')
            ->orderBy('name')
            ->get([
                'id', 'name', 'surname', 'email', 'phoneNumber', 'role', 'category',
                'varSpeciality', 'varProfile', 'chrApproval', 'clinic_id', 'created_at',
            ]);

        $availableDoctors = User::doctors()
            ->whereNull('clinic_id')
            ->orderBy('name')
            ->get(['id', 'name', 'surname', 'email', 'chrApproval', 'varProfile']);

        return view('admin.clinics.show', compact('clinic', 'members', 'availableDoctors'));
    }

    /**
     * Date-wise appointments booked at the clinic and how its daily tokens are occupied.
     */
    public function appointments(Request $request, Clinic $clinic)
    {
        $date = $this->selectedDate($request->input('date'));
        $dateString = $date->toDateString();

        $appointments = Appointment::query()
            ->where('clinic_id', $clinic->id)
            ->where('varAppointment', $dateString)
            ->with(['patient:id,name,lastname,phone', 'doctor:id,name,surname'])
            // Token holders first in token order, then anything without a token.
            ->orderByRaw('token_no IS NULL')
            ->orderBy('token_no')
            ->orderBy('startTime')
            ->get();

        $isActive = fn (Appointment $appointment) => $appointment->chrIsCanceled !== 'Y' && $appointment->chrIsRejected !== 'Y';

        // Tokens held by active bookings, keyed by number (same rule the apps use).
        $occupied = $appointments
            ->filter(fn (Appointment $appointment) => $isActive($appointment) && $appointment->token_no !== null)
            ->keyBy(fn (Appointment $appointment) => (int) $appointment->token_no);

        $highestBooked = $occupied->keys()->max() ?? 0;
        $totalTokens = max($clinic->max_tokens ?: $highestBooked + 1, $highestBooked);

        $runningToken = (int) ClinicTokenQueue::where('clinic_id', $clinic->id)
            ->where('queue_date', $dateString)
            ->value('current_token');

        $stats = [
            'occupied' => $occupied->count(),
            'available' => max(0, $totalTokens - $occupied->count()),
            'present' => $occupied->filter(fn (Appointment $appointment) => (int) $appointment->is_present === 1)->count(),
            'cancelled' => $appointments->reject($isActive)->count(),
        ];

        return view('admin.clinics.appointments', compact(
            'clinic', 'date', 'appointments', 'occupied', 'totalTokens', 'runningToken', 'stats'
        ));
    }

    public function edit(Clinic $clinic)
    {
        return view('admin.clinics.form', array_merge(
            ['clinic' => $clinic],
            $this->locationOptions(old('state', $clinic->state))
        ));
    }

    public function update(Request $request, Clinic $clinic)
    {
        $validated = $this->validateClinic($request);

        $clinic->fill(Arr::except($validated, ['logo']));
        $clinic->is_active = $request->boolean('is_active');

        if ($request->hasFile('logo')) {
            $this->deleteLogo($clinic->logo);
            $clinic->logo = $this->storeLogo($request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($clinic->logo);
            $clinic->logo = null;
        }

        $clinic->save();

        return redirect()
            ->route('admin.clinic.show', $clinic)
            ->with('success', 'Clinic profile updated.');
    }

    public function destroy(Clinic $clinic)
    {
        if ($clinic->members()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Remove all doctors and employees from ' . $clinic->name . ' before deleting it.');
        }

        $this->deleteLogo($clinic->logo);
        $clinic->delete();

        return redirect()
            ->route('admin.clinic')
            ->with('success', $clinic->name . ' was deleted.');
    }

    private function validateClinic(Request $request): array
    {
        $website = trim((string) $request->input('website', ''));
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $request->merge(['website' => 'https://' . $website]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'country' => 'nullable|integer|exists:country_master,id',
            'state' => 'nullable|integer|exists:states,id',
            'city_id' => ['nullable', 'integer', $this->cityInSelectedStateRule($request)],
            'postal_code' => 'nullable|string|max:20',
            'timings' => 'nullable|array|max:6',
            'timings.*.start' => 'nullable|date_format:H:i',
            'timings.*.end' => 'nullable|date_format:H:i',
            'max_tokens' => 'nullable|integer|min:1|max:1000',
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'city_id.exists' => 'The selected city does not belong to the selected state.',
            'logo.max' => 'The logo must not be larger than 2 MB.',
            'timings.max' => 'A clinic can have at most 6 sessions a day.',
            'timings.*.start.date_format' => 'Enter a valid start time.',
            'timings.*.end.date_format' => 'Enter a valid end time.',
        ], [
            'name' => 'clinic name',
            'registration_no' => 'registration number',
            'city_id' => 'city',
            'max_tokens' => 'max tokens',
        ]);

        $validated['timings'] = $this->normalizeTimings($validated['timings'] ?? []);

        return $validated;
    }

    /**
     * Drops blank rows, checks each session ends after it starts and that
     * sessions don't overlap, and returns them sorted by start time.
     */
    private function normalizeTimings(array $sessions): ?array
    {
        $errors = [];
        $clean = [];

        foreach ($sessions as $index => $session) {
            $start = $session['start'] ?? null;
            $end = $session['end'] ?? null;

            if (! $start && ! $end) {
                continue;
            }

            if (! $start || ! $end) {
                $errors["timings.$index." . ($start ? 'end' : 'start')] = 'Enter both a start and an end time for this session.';
                continue;
            }

            // H:i strings compare correctly as text.
            if ($end <= $start) {
                $errors["timings.$index.end"] = 'The session must end after it starts.';
                continue;
            }

            $clean[] = ['index' => $index, 'start' => $start, 'end' => $end];
        }

        usort($clean, fn ($a, $b) => strcmp($a['start'], $b['start']));

        for ($i = 1; $i < count($clean); $i++) {
            if ($clean[$i]['start'] < $clean[$i - 1]['end']) {
                $errors["timings.{$clean[$i]['index']}.start"] = 'This session overlaps another session.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $clean
            ? array_map(fn ($session) => ['start' => $session['start'], 'end' => $session['end']], $clean)
            : null;
    }

    private function selectedDate(?string $input): Carbon
    {
        if ($input && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            $parsed = Carbon::createFromFormat('!Y-m-d', $input);

            // Carbon rolls impossible dates over (2026-02-31 becomes 2026-03-03); treat those as invalid.
            if ($parsed && $parsed->format('Y-m-d') === $input) {
                return $parsed;
            }
        }

        return Carbon::today();
    }

    private function storeLogo(UploadedFile $file): string
    {
        $fileName = time() . '_' . Str::random(8) . '.' . $file->extension();
        $file->move(public_path(Clinic::LOGO_PATH), $fileName);

        return $fileName;
    }

    private function deleteLogo(?string $fileName): void
    {
        if (! $fileName) {
            return;
        }

        $path = public_path(Clinic::LOGO_PATH . '/' . $fileName);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
