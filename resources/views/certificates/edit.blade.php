@extends('adminlte::page')

@section('title', 'Edit Certificate')

@section('content_header')
    <h1>Edit Certificate</h1>
@stop

@section('content')
    @include('partials.flash')

    @php
        $clientOptions = $clients->sortBy('last_name')->map(function ($client) {
            $dob = $client->date_of_birth
                ? \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y')
                : 'N/A';

            return [
                'id' => $client->id,
                'label' => sprintf(
                    '%s, %s - MRN: %s - DOB: %s',
                    strtoupper($client->last_name),
                    strtoupper($client->first_name),
                    $client->mrn ?? 'N/A',
                    $dob
                ),
            ];
        })->values();
    @endphp

    <form method="POST" action="{{ route('certificates.update', $certificate) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header"><strong>Certificate Details</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-6">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" class="form-control select2" required>
                        <option value="">Select Client</option>
                        @foreach ($clientOptions as $clientOption)
                            <option value="{{ $clientOption['id'] }}" @selected($certificate->client_id === $clientOption['id'])>
                                {{ $clientOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="certificate_type_id">Certificate Type</label>
                    <select name="certificate_type_id" id="certificate_type_id" class="form-control" required>
                        <option value="">Select Type</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected($certificate->certificate_type_id === $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="graduation_date">Graduation Date</label>
                    <input type="date" name="graduation_date" id="graduation_date" class="form-control"
                           value="{{ $certificate->graduation_date?->format('Y-m-d') }}" required>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="{{ route('certificates.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Type to search...",
                allowClear: true
            });
        });
    </script>
@stop
