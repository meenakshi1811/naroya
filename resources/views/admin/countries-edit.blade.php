@extends('admin.admin') <!-- Adjust according to your layout -->

@section('content')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <div class="app-content-header"> <!--begin::Container-->
    <div class="container-fluid"> <!--begin::Row-->
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Edit Country</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ url('/admin/country') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Country
                    </li>
                </ol>
            </div>
        </div> <!--end::Row-->
    </div> <!--end::Container-->
    </div>
    <div class="card card-primary card-outline mb-4">
    <div class="card-body">
    <form method="POST" action="{{ route('admin.country.update', $country->id) }}">
        @csrf
        @method('PUT') <!-- Use PUT for updates -->

        <div class="mb-3">
            <label for="countrycode" class="form-label">Country Code</label>
            <input type="text" class="form-control @error('countrycode') is-invalid @enderror" id="countrycode" name="countrycode" value="{{ old('countrycode', $country->countrycode) }}" required>
            @error('countrycode')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="countryname" class="form-label">Country Name</label>
            <input type="text" class="form-control @error('countryname') is-invalid @enderror" id="countryname" name="countryname" value="{{ old('countryname', $country->countryname) }}" required>
            @error('countryname')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="code" class="form-label">Code</label>
            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $country->code) }}" required>
            @error('code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="phonecode" class="form-label">Phone Code</label>
            <input type="text" class="form-control @error('phonecode') is-invalid @enderror" id="phonecode" name="phonecode" value="{{ old('phonecode', $country->phonecode) }}" required>
            @error('phonecode')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="chrPublish" class="form-label">Publish</label>
            <input type="checkbox" id="chrPublish" name="chrPublish" value="1" {{ old('chrPublish', $country->chrPublish) ? 'checked' : '' }}>
        </div>

        <button type="submit" class="btn btn-primary">Update Country</button>
        <a href="{{ route('admin.country') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</div>
@endsection