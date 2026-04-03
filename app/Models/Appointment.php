<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;



    protected $table = 'appointment';


    protected $fillable = [
        'id',
        'patient_id',
        'dr_id',
        'varAppointment',
        'startTime',
        'endTime',
        'varSympton',
        'varSymptondesc',
        'chrIsAccepted',
        'chrIsRejected',
        'varReason',
        'charIsPaid',
        'created_at',
        'updated_at'
    ];

    public static function getAcceptedList($patientId = false)
    {
        $currentTime = Carbon::now();  // Get the current time
        
        $twelveHoursLater = $currentTime->copy()->addHours(12);
        $response = Appointment::select('appointment.*', 'users.name as Doctor Name', 'users.surname as surname', 'users.varProfile as varProfile', 'dr_category.title as Speciality','users.varFees as amount')
            ->where('patient_id', $patientId)
            ->where('chrIsAccepted', 'Y')
            ->where('chrIsRejected', 'N')
            ->where('chrIsCanceled','N')
            ->join('users', 'dr_id', 'users.id')
            ->join('dr_category', 'users.category', 'dr_category.id')
            ->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Only appointments for today or in the future
            ->where(function ($query) use ($currentTime, $twelveHoursLater) {
                // If the appointment is today, check if it is within the next 12 hours
                $query->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Appointments scheduled after today
                    ->orWhere(function ($subQuery) use ($currentTime, $twelveHoursLater) {
                        // If the appointment is today, check if the start time is within the next 12 hours
                        $subQuery->whereDate('appointment.varAppointment', '=', $currentTime->toDateString())  // Today
                            ->whereTime('appointment.startTime', '>', $currentTime->toTimeString())  // Appointment start time after now
                            ->whereTime('appointment.startTime', '<=', $twelveHoursLater->toTimeString());  // Appointment start time within next 12 hours
                    });
            })
            ->orderBy('appointment.varAppointment', 'desc')  // First, sort by date
            ->orderBy('appointment.startTime', 'asc')
            ->get();

        return $response;
    }


    public static function getAllAcceptedList($patientId = false, $limit, $page)
    {
        $currentTime = Carbon::now();  // Get the current time
        $twelveHoursLater = $currentTime->copy()->addHours(12);
        $response = Appointment::select('appointment.*','users.name as Doctor Name', 'users.surname as surname', 'users.varProfile as varProfile', 'dr_category.title as Speciality','users.varFees as amount')
            ->where('patient_id', $patientId)
            ->where('chrIsAccepted', 'Y')
            ->where('chrIsRejected', 'N')
            ->where('chrIsCanceled','N')
            ->join('users', 'dr_id', 'users.id')
            ->join('dr_category', 'users.category', 'dr_category.id')
            ->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Only appointments for today or in the future
            ->where(function ($query) use ($currentTime, $twelveHoursLater) {
                // If the appointment is today, check if it is within the next 12 hours
                $query->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Appointments scheduled after today
                    ->orWhere(function ($subQuery) use ($currentTime, $twelveHoursLater) {
                        // If the appointment is today, check if the start time is within the next 12 hours
                        $subQuery->whereDate('appointment.varAppointment', '=', $currentTime->toDateString())  // Today
                            ->whereTime('appointment.startTime', '>', $currentTime->toTimeString())  // Appointment start time after now
                            ->whereTime('appointment.startTime', '<=', $twelveHoursLater->toTimeString());  // Appointment start time within next 12 hours
                    });
            })
            ->orderBy('appointment.varAppointment', 'desc')  // First, sort by date
            ->orderBy('appointment.startTime', 'asc')
            ->get();
        // ->paginate($limit, ['*'], 'page', $page);

        return $response;
    }

    public static function getRejectedList($patientId = false)
    {
        $currentTime = Carbon::now();  // Get the current time
        $twelveHoursLater = $currentTime->copy()->addHours(12);  // Time 12 hours later from now

        // Query appointments based on conditions
        $response = Appointment::select(
            'appointment.*',
            'users.name as Doctor Name',
            'users.surname as surname',
            'users.varProfile as varProfile',
            'dr_category.title as Speciality'
        )
            ->where('patient_id', $patientId)
            ->where('chrIsAccepted', 'N')
            ->where('chrIsRejected', 'Y')
            ->join('users', 'dr_id', 'users.id')
            ->join('dr_category', 'users.category', 'dr_category.id')
            ->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Only appointments for today or in the future
            ->where(function ($query) use ($currentTime, $twelveHoursLater) {
                // If the appointment is today, check if it is within the next 12 hours
                $query->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Appointments scheduled after today
                    ->orWhere(function ($subQuery) use ($currentTime, $twelveHoursLater) {
                        // If the appointment is today, check if the start time is within the next 12 hours
                        $subQuery->whereDate('appointment.varAppointment', '=', $currentTime->toDateString())  // Today
                            ->whereTime('appointment.startTime', '>', $currentTime->toTimeString())  // Appointment start time after now
                            ->whereTime('appointment.startTime', '<=', $twelveHoursLater->toTimeString());  // Appointment start time within next 12 hours
                    });
            })
            ->orderBy('appointment.varAppointment', 'desc')  // Sort by appointment date
            ->orderBy('appointment.startTime', 'asc')  // Sort by appointment start time                               
            ->get();

        return $response;
    }
    public static function getAllRejectedList($patientId = false, $limit, $page)
    {
        $currentTime = Carbon::now();  // Get the current time
        $twelveHoursLater = $currentTime->copy()->addHours(12);
        $response = Appointment::select('appointment.*', 'users.name as Doctor Name', 'users.surname as surname', 'users.varProfile as varProfile', 'dr_category.title as Speciality')
            ->where('patient_id', $patientId)
            ->where('chrIsAccepted', 'N')
            ->where('chrIsRejected', 'Y')
            ->join('users', 'dr_id', 'users.id')
            ->join('dr_category', 'users.category', 'dr_category.id')
            ->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Only appointments for today or in the future
            ->where(function ($query) use ($currentTime, $twelveHoursLater) {
                // If the appointment is today, check if it is within the next 12 hours
                $query->whereDate('appointment.varAppointment', '>=', $currentTime->toDateString())  // Appointments scheduled after today
                    ->orWhere(function ($subQuery) use ($currentTime, $twelveHoursLater) {
                        // If the appointment is today, check if the start time is within the next 12 hours
                        $subQuery->whereDate('appointment.varAppointment', '=', $currentTime->toDateString())  // Today
                            ->whereTime('appointment.startTime', '>', $currentTime->toTimeString())  // Appointment start time after now
                            ->whereTime('appointment.startTime', '<=', $twelveHoursLater->toTimeString());  // Appointment start time within next 12 hours
                    });
            })
            ->orderBy('appointment.varAppointment', 'desc')  // First, sort by date
            ->orderBy('appointment.startTime', 'asc')
            ->get();
        // ->paginate($limit, ['*'], 'page', $page);

        return $response;
    }
}
