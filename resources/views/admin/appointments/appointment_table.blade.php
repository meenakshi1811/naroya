@if(isset($appointmentData) && count($appointmentData) > 0)
@foreach($appointmentData as $data)
<tr class="align-middle">
    <td class="text-center">{{ $data->id }}</td>
    <td class="text-center">{{ $data->patient .' '. $data->lastname }}</td>
    <td class="text-center">{{ $data->doctor .' '. $data->surname }}</td>
    <td class="text-center">{{ $data->speciality }}</td>
    <td class="text-center">{{ \Carbon\Carbon::parse($data->varAppointment)->format('d F Y') }}</td>
    <td class="text-center">{!! $data->startTime !!} - {!! $data->endTime !!}</td>
    <td class="text-center">{{ $data->varSympton }}</td>
    <td class="text-center">{!! $data->varSymptondesc !!}</td>
    <td class="text-center">{!! !empty($data->country)? $data->country : '-' !!}</td>  
    <td class="text-center">{!! !empty($data->state)? $data->state : '-' !!}</td>  
</tr>
@endforeach
@else
<tr>
    <td colspan="10" class="text-center">No records found</td>
</tr>
@endif    
