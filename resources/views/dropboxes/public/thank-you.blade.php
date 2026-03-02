@extends('layouts.dropbox')

@section('title', 'Intake Submitted')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <h2 class="mb-3">Thank you!</h2>
                    <p class="text-muted mb-4">Your information has been received. Our team will review the submission and reach out if we need anything else.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="{{ route('dropbox.intake') }}" class="btn btn-outline-primary">Submit Another</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
