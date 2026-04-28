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
                <h3 class="card-title mb-0">Languages</h3>
                <a href="{{ route('admin.language.add') }}" class="btn btn-primary">Add Language</a>
            </div>
            <div class="card-body">
                <table id="datatable" class="table table-bordered table-hover pending-doctors-table">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Language</th>
                            <th>Publish</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($languages) && count($languages) > 0)
                        @foreach($languages as $language)
                        @php
                            $isPublished = $language->chrPublish === 'Y';
                        @endphp
                        <tr class="align-middle">
                            <td>{{ $language->id }}</td>
                            <td>{{ $language->language_name }}</td>
                            <td>
                                <span class="badge status-badge {{ $isPublished ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $isPublished ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="action-buttons">
                                    <a href="{{ url('/admin/language/' . $language->id . '/edit') }}" type="button" class="btn btn-outline-info">edit</a>
                                    <form action="{{ route('admin.language.delete', $language->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this language?');">Delete</button>
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
