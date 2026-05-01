


@extends('admin.admin')

@section('title', isset($speciality) ? 'Edit Speciality' : 'Add Speciality')

@section('content_header')
    <h1>{{ isset($speciality) ? 'Edit Speciality' : 'Add Speciality' }}</h1>
@endsection

@section('content')

<div class="app-content-header"> <!--begin::Container-->
    <div class="container-fluid"> <!--begin::Row-->
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">{{ isset($speciality) ? 'Edit Speciality ' . $speciality->title : 'Add New Speciality' }}</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ url('/admin/speciality') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Speciality
                    </li>
                </ol>
            </div>
        </div> <!--end::Row-->
    </div> <!--end::Container-->
</div>

<div class="card card-primary card-outline mb-4">
    <div class="card-body">
        <form action="{{ isset($speciality) ? route('speciality.update', $speciality->id) : route('admin.speciality.store') }}" method="POST">
            @csrf
            @method(isset($speciality) ? 'PUT' : 'POST') <!-- Use PUT if editing, POST if adding -->
            <div class="row">
                <div class="mb-3">
                    <div class="form-group">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $speciality->title ?? '') }}" required>
                    </div>
                </div>
            </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">{{ isset($speciality) ? 'Update' : 'Add' }}</button>
        <a href="{{ url('/admin/speciality') }}" class="btn btn-secondary">Cancel</a>
    </form>
    </div>
</div>
@endsection

