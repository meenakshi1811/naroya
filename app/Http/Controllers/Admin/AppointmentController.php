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
        $query = Appointment::query()
            ->where('appointment.charIsPaid', 'Y')
            ->where('appointment.chrIsCanceled', 'N');

        // Filters
        if ($request) {
            if ($request->date) {
                $query->whereDate('appointment.varAppointment', $request->date);
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
                    $q->where('appointment.varAppointment', 'like', '%' . $search . '%')
                        ->orWhere('appointment.startTime', 'like', '%' . $search . '%')
                        ->orWhere('appointment.endTime', 'like', '%' . $search . '%')
                        ->orWhere('appointment.varSympton', 'like', '%' . $search . '%')
                        ->orWhere('appointment.varSymptondesc', 'like', '%' . $search . '%')
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

        $appointments = $query
            ->leftJoin('patients', 'appointment.patient_id', '=', 'patients.id')
            ->leftJoin('users', 'appointment.dr_id', '=', 'users.id')
            ->leftJoin('dr_category', 'users.category', '=', 'dr_category.id')
            ->leftJoin('country_master', 'users.country', '=', 'country_master.id')
            ->leftJoin('states', 'users.state', '=', 'states.id')
            ->select([
                'appointment.*',
                'patients.name as patient_first_name',
                'patients.lastname as patient_last_name',
                'users.name as doctor_first_name',
                'users.surname as doctor_surname',
                'dr_category.title as speciality',
                'country_master.countryname as country',
                'states.name as state',
            ])
            ->orderBy('appointment.varAppointment', 'desc')
            ->orderBy('appointment.startTime', 'asc')
            ->paginate(10)
            ->withPath(route('appointments.filter'))
            ->appends($request?->query() ?? []);

        $appointments->getCollection()->transform(function ($item) {
            $patientName = trim(collect([$item->patient_first_name, $item->patient_last_name])->filter()->implode(' '));
            $doctorName = trim(collect([$item->doctor_first_name, $item->doctor_surname])->filter()->implode(' '));

            return (object) [
                'id' => $item->id,
                'patient' => $patientName !== '' ? $patientName : '-',
                'doctor' => $doctorName !== '' ? $doctorName : '-',
                'speciality' => $item->speciality ?: '-',
                'varAppointment' => $item->varAppointment,
                'startTime' => $item->startTime,
                'endTime' => $item->endTime,
                'varSympton' => $item->varSympton,
                'varSymptondesc' => $item->varSymptondesc,
                'chrIsAccepted' => $item->chrIsAccepted,
                'country' => $item->country,
                'state' => $item->state,
            ];
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
