@extends('admin.admin')
@section('content')
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet">

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 pending-doctors-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Speciality</h3>
                <a href="{{ route('admin.speciality.add') }}" class="btn btn-primary">Add Speciality</a>
            </div>
            <div class="card-body">
                <table id="datatable" class="table table-bordered table-hover pending-doctors-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px">#</th>
                            <th>Name</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($speciality) && count($speciality) > 0)
                        @foreach($speciality as $data)
                        <tr class="align-middle">
                            <td>{{ $data->id }}</td>
                            <td>{{ $data->title }}</td>
                            <td class="text-center">
                                <div class="action-buttons">
                                    <a href="{{ url('/admin/speciality/' . $data->id . '/edit') }}" type="button" class="btn btn-outline-info">edit</a>
                                    <form action="{{ route('admin.speciality.delete', $data->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this Speicality?');">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="3" class="text-center">No records found</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#datatable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "lengthChange": true,
            "autoWidth": false,
            "columnDefs": [{
                "targets": [0],
                "orderable": false
            }]
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
@endsection
