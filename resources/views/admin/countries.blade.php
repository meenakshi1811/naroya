@extends('admin.admin')
@section('content')
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet">

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 admin-list-card">
            <div class="card-header admin-list-header">
                <h3 class="card-title mb-0">Countries</h3>
                <a href="{{ route('admin.country.add') }}" class="btn btn-primary admin-list-add-button">Add Country</a>
            </div>
            <div class="card-body">
                <div class="table-responsive admin-list-table-wrap">
                    <table id="datatable" class="table table-bordered table-hover admin-list-table">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Name</th>
                            <th>Country code</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($countries) && count($countries) > 0)
                        @foreach($countries as $country)
                        <tr class="align-middle">
                            <td>{{ $country->id }}</td>
                            <td>{{ $country->countryname }}</td>
                            <td>{{ $country->countrycode }}</td>
                            <td class="text-center">
                                <div class="action-buttons admin-list-actions">
                                    <a href="{{ url('/admin/country/' . $country->id . '/edit') }}" type="button" class="btn btn-outline-info">edit</a>
                                    <form action="{{ route('admin.country.delete', $country->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this Country?');">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="4" class="text-center">No records found</td>
                        </tr>
                        @endif
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
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
@endsection
