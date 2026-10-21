<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ProvidesLocationOptions;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

        return $request->validate([
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
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'city_id.exists' => 'The selected city does not belong to the selected state.',
            'logo.max' => 'The logo must not be larger than 2 MB.',
        ], [
            'name' => 'clinic name',
            'registration_no' => 'registration number',
            'city_id' => 'city',
        ]);
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
