@extends('adminlte::page')

@section('title', 'Authorization Details')

@section('content_header')
    <h1>Authorization Details</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h5><strong>Client:</strong> {{ $authorization->client->name }} (MRN: {{ $authorization->client->mrn }})</h5>
            <p><strong>DOB:</strong> {{ \Carbon\Carbon::parse($authorization->client->date_of_birth)->format('m/d/Y') }}</p>
            <p><strong>Carelon Member ID:</strong>
                @can('client.view_carelon_id')
                    {{ $authorization->client->carelon_member_id }}
                @else
                    Restricted
                @endcan
            </p>
            <p><strong>Medicaid ID:</strong>
                @can('client.view_medicaid_id')
                    {{ $authorization->client->medicaid_id }}
                @else
                    Restricted
                @endcan
            </p>
            <hr>

            {{-- <p><strong>Admit Date:</strong> {{ $authorization->admit_date }}</p>
            <p><strong>Auth Type:</strong> {{ ucfirst($authorization->auth_type) }}</p> --}}
            <p><strong>LOC:</strong> {{ $authorization->levelOfCare?->display_name ?? '-' }}</p>
            {{-- <p><strong>Auth Submission Date:</strong> {{ $authorization->auth_submission_date }}</p> --}}
            <p><strong>Auth Number:</strong> {{ $authorization->auth_number }}</p>
            {{-- <p><strong>Auth Status:</strong>
                <span class="badge badge-{{ $authorization->auth_status == 'approved' ? 'success' : ($authorization->auth_status == 'pending' ? 'warning' : ($authorization->auth_status == 'denied' ? 'danger' : 'secondary')) }}">
                    {{ ucfirst($authorization->auth_status) }}
                </span>
            </p>
            <p><strong>Units:</strong> {{ $authorization->units }}</p>
            <p><strong>Auth Starting Date:</strong> {{ $authorization->auth_starting_date }}</p>
            <p><strong>Auth Ending Date:</strong> {{ $authorization->auth_ending_date }}</p>
            <p><strong>Diagnosis Code:</strong> {{ $authorization->diagnosis_code }}</p> --}}
            <p><strong>Remarks:</strong> {{ $authorization->remarks }}</p>

            <p><strong>Attachments:</strong></p>
            <ul>
                @foreach(json_decode($authorization->attachment, true) as $file)
                    <li><a href="{{ Storage::url('attachments/' . $file) }}" target="_blank">{{ $file }}</a></li>
                @endforeach
            </ul>

            <a href="{{ route('authorizations.edit', $authorization->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('authorizations.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
@stop
