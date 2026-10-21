@if(session('success'))
    <div class="cm-alert cm-alert-success" role="status">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" aria-label="Dismiss" onclick="this.parentElement.remove()"></button>
    </div>
@endif

@if(session('error'))
    <div class="cm-alert cm-alert-error" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" aria-label="Dismiss" onclick="this.parentElement.remove()"></button>
    </div>
@endif

@if($errors->any())
    <div class="cm-alert cm-alert-error" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div>
            <strong>Please fix the highlighted {{ $errors->count() === 1 ? 'field' : 'fields' }}.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" aria-label="Dismiss" onclick="this.parentElement.remove()"></button>
    </div>
@endif
