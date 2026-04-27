@extends('admin.admin')

@section('content')
    <div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Edit Language</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ url('/admin/language') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Language
                    </li>
                </ol>
            </div>
        </div>
    </div>
    </div>
    <div class="card card-primary card-outline mb-4">
    <div class="card-body">
    <form method="POST" action="{{ route('admin.language.update', $language->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="language_name" class="form-label">Language Name</label>
            <input type="text" class="form-control @error('language_name') is-invalid @enderror" id="language_name" name="language_name" value="{{ old('language_name', $language->language_name) }}" required>
            @error('language_name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="chrPublish" class="form-label">Publish</label>
            <input type="checkbox" id="chrPublish" name="chrPublish" value="1" {{ old('chrPublish', $language->chrPublish === 'Y') ? 'checked' : '' }}>
        </div>

        <button type="submit" class="btn btn-primary">Update Language</button>
        <a href="{{ route('admin.language') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</div>
@endsection
