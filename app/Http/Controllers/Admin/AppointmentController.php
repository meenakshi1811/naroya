<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Speciality;

class AppointmentController extends Controller
{


    public function index()
    {
        // Load initial appointments for the view
        $appointmentData = $this->getAppointments();
        return view('admin.appointments.appointment', compact('appointmentData'));
    }

    public function filter(Request $request)
    {
        $appointmentData = $this->getAppointments($request);

        return response()->json([
            'rows' => view('admin.appointments.appointment_table', compact('appointmentData'))->render(),
            'pagination' => $appointmentData->links('pagination::bootstrap-5')->render(),
        ]);
    }

    private function getAppointments(Request $request = null)
    {
        $query = Appointment::with([
            'patient:id,name,lastname',
            'doctor:id,name,surname,category,country,state',
            'doctor.speciality:id,title',
            'doctor.countryRel:id,countryname',
            'doctor.stateRel:id,name'
        ]);

        // Filters
        if ($request) {
            if ($request->date) {
                $query->whereDate('varAppointment', $request->date);
            }

            if ($request->doctor) {
                $query->whereHas('doctor', function ($q) use ($request) {
                    $q->whereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$request->doctor}%"]);
                });
            }

            if ($request->speciality) {
                $query->whereHas('doctor.speciality', function ($q) use ($request) {
                    $q->where('id', $request->speciality);
                });
            }

            if ($request->country) {
                $query->whereHas('doctor.countryRel', function ($q) use ($request) {
                    $q->where('countryname', 'like', '%' . $request->country . '%');
                });
            }

            if ($request->state) {
                $query->whereHas('doctor.stateRel', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->state . '%');
                });
            }

            if ($request->search) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('varAppointment', 'like', '%' . $search . '%')
                        ->orWhere('startTime', 'like', '%' . $search . '%')
                        ->orWhere('endTime', 'like', '%' . $search . '%')
                        ->orWhere('varSympton', 'like', '%' . $search . '%')
                        ->orWhere('varSymptondesc', 'like', '%' . $search . '%')
                        ->orWhereHas('patient', function ($patientQuery) use ($search) {
                            $patientQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('lastname', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('doctor', function ($doctorQuery) use ($search) {
                            $doctorQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('surname', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('doctor.speciality', function ($specialityQuery) use ($search) {
                            $specialityQuery->where('title', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('doctor.countryRel', function ($countryQuery) use ($search) {
                            $countryQuery->where('countryname', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('doctor.stateRel', function ($stateQuery) use ($search) {
                            $stateQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
        }

        $appointments = $query->orderBy('varAppointment', 'desc')
            ->orderBy('startTime', 'asc')
            ->paginate(10)
            ->withPath(route('appointments.filter'))
            ->appends($request?->query() ?? []);

        $appointments->getCollection()->transform(function ($item) {
            return (object) array_merge($item->getAttributes(), [
                'patient' => $item->patient?->name,
                'lastname' => $item->patient?->lastname,
                'doctor' => $item->doctor?->name,
                'surname' => $item->doctor?->surname,
                'speciality' => $item->doctor?->speciality?->title,
                'speciality_id' => $item->doctor?->speciality?->id,
                'country' => $item->doctor?->countryRel?->countryname,
                'state' => $item->doctor?->stateRel?->name,
            ]);
        });

        return $appointments;
    }

  
    public function getSpecialities()
    {
        $specialities = Speciality::all();
        $options = '';
        foreach ($specialities as $speciality) {
            $options .= '<option value="' . $speciality->id . '">' . $speciality->title . '</option>';
        }
        return response()->json($options);
    }

}
