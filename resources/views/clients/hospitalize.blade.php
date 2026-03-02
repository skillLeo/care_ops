@extends('adminlte::page')

@section('title', 'Hospitalize - ' . $client->full_name)

@section('content_header')
    <h1>Hospitalize Client: {{ $client->full_name }}</h1>
@stop

@section('content')
    @include('clients.partials.info-card')
    <form method="POST" action="{{ route('clients.hospitalize.submit', $client->id) }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <h4>Hospitalization Details</h4>
                <div class="form-group">
                    <label>Hospitalization Date</label>
                    <input type="date" name="hospitalization_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="hospitalization_type" class="form-control" required>
                        <option value="" selected disabled>Select Type</option>
                        <option value="detox">Detox</option>
                        <option value="hospitalization">Hospitalization</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode of Transport</label>
                    <input type="text" name="mode_of_transport" class="form-control">
                </div>
                <div class="form-group">
                    <label>Facility</label>
                    <select name="hospitalization_facility_id" class="form-control">
                        <option value="">Select Facility</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}">
                                {{ $facility->name }}
                                @if ($facility->address)
                                    - {{ $facility->address }}
                                @endif
                                ({{ ucfirst($facility->type ?? 'N/A') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-danger">Submit Hospitalization</button>
        <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop
