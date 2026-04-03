@extends('admin.admin')
@section('content')
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif
<!-- Bootstrap CSS -->
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- jQuery and Bootstrap JS -->
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Country
                </h2>
                <a href="{{ route('admin.country.add') }}" class="btn btn-primary float-end">Add Country</a>
            </div> <!-- /.card-header -->
            <div class="card-body">
                <table id="datatable" class="table table-bordered">
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
                        @foreach($countries as $key => $country)
                        <tr class="align-middle">
                            <td>{{ $country->id }}</td>
                            <td>{{ $country->countryname }}</td>
                            @if(!empty($country->code))
                            @php
                            $countryImage = config('app.url').'flags/'.strtolower($country->code).'.png';
                            @endphp
                            @endif
                            {{--<td><img src="{{$countryImage }}" alt="{{ $country->countryname }}" height="50px" width="60px"/></td> --}}
                            <td>{{ $country->countrycode }}</td>
                            <td class="text-center"><a href="{{ url('/admin/country/' . $country->id . '/edit') }}" type="button" class="btn btn-info">edit</a>


                                <form action="{{ route('admin.country.delete', $country->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this Country?');">
                                        Delete
                                    </button>
                                </form>
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
            </div> <!-- /.card-body -->
            <!-- <div class="card-footer clearfix">
                                    <ul class="pagination pagination-sm m-0 float-end">
                                        <li class="page-item"> <a class="page-link" href="#">&laquo;</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">1</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">2</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">3</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">&raquo;</a> </li>
                                    </ul>
                                </div> -->
        </div> <!-- /.card -->

    </div> <!-- /.col -->

</div>
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable
        $('#datatable').DataTable({
            "paging": true, // Enable pagination
            "searching": true, // Enable search
            "ordering": true, // Enable sorting
            "info": true, // Enable info text (e.g., "Showing 1 to 10 of 50 entries")
            "lengthChange": true, // Allow changing the number of rows per page
            "autoWidth": false, // Disable automatic column width calculation
            "columnDefs": [{
                "targets": [0], // Disable sorting for the ID column (optional)
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