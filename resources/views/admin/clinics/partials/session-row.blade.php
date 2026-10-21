@php
    $startError = $errors->first("timings.$index.start");
    $endError = $errors->first("timings.$index.end");
@endphp
<div class="cm-session" data-session-row>
    <span class="cm-session-label" aria-hidden="true"></span>
    <input type="time" class="form-control {{ $startError ? 'is-invalid' : '' }}" name="timings[{{ $index }}][start]" value="{{ $session['start'] ?? '' }}" aria-label="Session opens at">
    <span class="cm-session-sep">to</span>
    <input type="time" class="form-control {{ $endError ? 'is-invalid' : '' }}" name="timings[{{ $index }}][end]" value="{{ $session['end'] ?? '' }}" aria-label="Session closes at">
    <button type="button" class="cm-icon-btn is-danger" data-remove-session title="Remove session" aria-label="Remove session">
        <i class="bi bi-x-lg"></i>
    </button>
    @if($startError || $endError)
        <div class="cm-session-error">{{ $startError ?: $endError }}</div>
    @endif
</div>
