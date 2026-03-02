@php
    $currentFont = old('name_font', $certificateType->name_font ?? 'brittanysignature');
@endphp

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="name">Certificate Type Name</label>
                <input type="text" name="name" id="name" class="form-control"
                       value="{{ old('name', $certificateType->name ?? '') }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="template">Certificate Template (PDF, Letter Landscape)</label>
                <input type="file" name="template" id="template" class="form-control" @if(empty($certificateType)) required @endif>
                @if (!empty($certificateType) && $certificateType->template_path)
                    <small class="text-muted">Current file: {{ basename($certificateType->template_path) }}</small>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-3">
                <label for="name_x">Name X</label>
                <input type="number" step="0.1" name="name_x" id="name_x" class="form-control"
                       value="{{ old('name_x', $certificateType->name_x ?? '') }}" required>
            </div>
            <div class="form-group col-md-3">
                <label for="name_y">Name Y</label>
                <input type="number" step="0.1" name="name_y" id="name_y" class="form-control"
                       value="{{ old('name_y', $certificateType->name_y ?? '') }}" required>
            </div>
            <div class="form-group col-md-3">
                <label for="date_x">Date X</label>
                <input type="number" step="0.1" name="date_x" id="date_x" class="form-control"
                       value="{{ old('date_x', $certificateType->date_x ?? '') }}" required>
            </div>
            <div class="form-group col-md-3">
                <label for="date_y">Date Y</label>
                <input type="number" step="0.1" name="date_y" id="date_y" class="form-control"
                       value="{{ old('date_y', $certificateType->date_y ?? '') }}" required>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-6">
                <label for="name_font">Name Font</label>
                <select name="name_font" id="name_font" class="form-control">
                    @foreach ($fontOptions as $fontKey => $fontLabel)
                        <option value="{{ $fontKey }}" @selected($currentFont === $fontKey)>{{ $fontLabel }}</option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">
                    Available fonts: {{ implode(', ', $fontOptions) }}
                </small>
            </div>
        </div>
    </div>
</div>
