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
        $query = Appointment::select('appointment.*', 'patients.name as patient','patients.lastname as lastname', 'users.name as doctor','users.surname as surname','dr_category.title as speciality','dr_category.id as speciality_id','country_master.countryname as country','states.name as state')
            ->join('patients', 'patient_id', 'patients.id')
            ->join('users', 'dr_id', 'users.id')
            ->join('dr_category', 'users.category', 'dr_category.id')
            ->join('country_master', 'users.country', 'country_master.id')
            ->join('states', 'users.state', 'states.id');

        // Apply filters based on user input
        if ($request) {
            if ($request->date) {
                $query->whereDate('varAppointment', $request->date);
            }
            if ($request->doctor) {
                $query->whereRaw("CONCAT(users.name, ' ', users.surname) LIKE ?", ["%{$request->doctor}%"]);
            }
            if ($request->speciality) {
                $query->where('dr_category.id', $request->speciality);
            }
            if ($request->country) {
                $query->where('country_master.countryname', 'like', '%' . $request->country . '%');
            }
            if ($request->state) {
                $query->where('states.name', 'like', '%' . $request->state . '%');
            }
        }

        return $query->get();
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
