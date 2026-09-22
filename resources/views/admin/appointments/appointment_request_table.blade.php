@if(isset($appointmentData) && count($appointmentData) > 0)
@foreach($appointmentData as $data)
<tr class="align-middle">
    <td class="text-center">{{ $data->id }}</td>
    <td class="text-center">{{ $data->patient }}</td>
    <td class="text-center">{{ $data->doctor }}</td>
    <td class="text-center">{{ $data->speciality }}</td>
    <td class="text-center">{{ \Carbon\Carbon::parse($data->varAppointment)->format('d F Y') }}</td>
    <td class="text-center">{!! $data->startTime !!} - {!! $data->endTime !!}</td>
    <td class="text-center">{{ $data->varSympton }}</td>
    <td class="text-center">{!! $data->varSymptondesc !!}</td>
    <td class="text-center">{!! ($data->chrIsAccepted == 'Y') ? 'Yes' : 'No' !!}</td>
    <td class="text-center">
        @if(!empty($data->is_freeslot))
            <span class="badge text-bg-success">Free slot</span>
        @else
            <span class="badge text-bg-secondary">Paid</span>
        @endif
    </td>
    <td class="text-center"><span class="badge text-bg-warning">Unpaid</span></td>
    <td class="text-center">{!! !empty($data->country) ? $data->country : '-' !!}</td>
    <td class="text-center">{!! !empty($data->state) ? $data->state : '-' !!}</td>
</tr>
@endforeach
@else
<tr>
    <td colspan="13" class="text-center">No records found</td>
</tr>
@endif
