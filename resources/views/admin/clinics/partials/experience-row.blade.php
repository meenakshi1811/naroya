@php
    $isCurrent = ($experience['is_current'] ?? 'N') === 'Y';
    $fieldId = 'experience_' . $index;
@endphp
<div class="cm-repeater-item" data-experience-row>
    <div class="cm-repeater-head">
        <span class="cm-repeater-title">Work experience</span>
        <button type="button" class="cm-icon-btn is-danger" data-remove-experience title="Remove" aria-label="Remove this experience">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="{{ $fieldId }}_org">Organisation</label>
            <input type="text" class="form-control @error("experiences.$index.org_name") is-invalid @enderror" id="{{ $fieldId }}_org" name="experiences[{{ $index }}][org_name]" value="{{ $experience['org_name'] ?? '' }}" placeholder="Hospital or clinic name" maxlength="255">
            @error("experiences.$index.org_name")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="{{ $fieldId }}_start">Start year</label>
            <input type="text" inputmode="numeric" class="form-control @error("experiences.$index.start_year") is-invalid @enderror" id="{{ $fieldId }}_start" name="experiences[{{ $index }}][start_year]" value="{{ $experience['start_year'] ?? '' }}" placeholder="YYYY" maxlength="20">
            @error("experiences.$index.start_year")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="{{ $fieldId }}_end">End year</label>
            <input type="text" inputmode="numeric" class="form-control @error("experiences.$index.end_year") is-invalid @enderror" id="{{ $fieldId }}_end" name="experiences[{{ $index }}][end_year]" value="{{ $isCurrent ? '' : ($experience['end_year'] ?? '') }}" placeholder="{{ $isCurrent ? 'Present' : 'YYYY' }}" maxlength="20" data-end-year @disabled($isCurrent)>
            @error("experiences.$index.end_year")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="{{ $fieldId }}_description">Role and responsibilities<span class="cm-optional">Optional</span></label>
            <textarea class="form-control" id="{{ $fieldId }}_description" name="experiences[{{ $index }}][description]" rows="2" maxlength="1000" placeholder="e.g. Consultant physician, outpatient department">{{ $experience['description'] ?? '' }}</textarea>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="{{ $fieldId }}_current" name="experiences[{{ $index }}][is_current]" value="Y" data-current-toggle @checked($isCurrent)>
                <label class="form-check-label" for="{{ $fieldId }}_current">Currently working here</label>
            </div>
        </div>
    </div>
</div>
