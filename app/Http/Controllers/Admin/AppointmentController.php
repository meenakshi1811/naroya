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
        
        // Return the filtered data as a partial view
        return view('admin.appointments.appointment_table', compact('appointmentData'));
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
        }

        $appointments = $query->get();

        
        return $appointments->map(function ($item) {
            return (object)[
                'id' => $item->id,
                'patient' => $item->patient->name ?? null,
                'lastname' => $item->patient->lastname ?? null,
                'doctor' => $item->doctor->name ?? null,
                'surname' => $item->doctor->surname ?? null,
                'speciality' => $item->doctor->speciality->title ?? null,
                'speciality_id' => $item->doctor->speciality->id ?? null,
                'country' => $item->doctor->countryRel->countryname ?? null,
                'state' => $item->doctor->stateRel->name ?? null,

                // include all appointment fields
                'varAppointment' => $item->varAppointment,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });
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
