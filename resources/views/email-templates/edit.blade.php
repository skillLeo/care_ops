@extends('adminlte::page')

@section('title', 'Edit Email Template')

@section('content_header')
    <h1>Edit Email Template</h1>
@stop

@section('content')
    <a href="{{ route('email-templates.index') }}" class="btn btn-secondary mb-3">Back to Templates</a>

    @if ($emailTemplate->key === 'clinical_note_tracker')
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-2 mb-sm-0">
                <strong>Test Emails:</strong>
                The daily chart compliance summary test emails will be sent to <strong>fawzan@snbllc.org</strong>.
                <div class="small text-muted">Scheduled send time: 8:00 AM daily.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('email-templates.clinical-notes-tracker.test') }}">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit">Send Test Emails</button>
                </form>
                <form method="POST" action="{{ route('email-templates.clinical-notes-tracker.send-now') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Send Manually Now</button>
                </form>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('email-templates.update', $emailTemplate) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="name">Template Name</label>
                            <input class="form-control" id="name" name="name" type="text" value="{{ old('name', $emailTemplate->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="subject">Subject</label>
                            <input class="form-control" id="subject" name="subject" type="text" value="{{ old('subject', $emailTemplate->subject) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="to">To</label>
                            @if ($emailTemplate->key === 'clinical_note_tracker')
                                <input class="form-control" id="to" name="to" type="text" value="{{ old('to', '[counselor_email]') }}" readonly>
                                <small class="text-muted">This template always sends to the counselor email placeholder.</small>
                            @else
                                <input class="form-control" id="to" name="to" type="text" value="{{ old('to', $emailTemplate->to) }}">
                                <small class="text-muted">Separate multiple emails with semicolons or commas. Placeholders like [counselor_email] are allowed.</small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="cc">CC</label>
                            <input class="form-control" id="cc" name="cc" type="text" value="{{ old('cc', $emailTemplate->cc) }}">
                            <small class="text-muted">Separate multiple emails with semicolons or commas.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="bcc">BCC</label>
                            <input class="form-control" id="bcc" name="bcc" type="text" value="{{ old('bcc', $emailTemplate->bcc) }}">
                            <small class="text-muted">Separate multiple emails with semicolons or commas.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="body">Body</label>
                            <textarea class="form-control" id="body" name="body" rows="12" required>{{ old('body', $emailTemplate->body) }}</textarea>
                        </div>

                        <button class="btn btn-primary" type="submit">Save Template</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <strong>Available Placeholders</strong>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @foreach ($placeholders as $placeholder => $description)
                            <li class="mb-2">
                                <code>{{ $placeholder }}</code>
                                <div class="text-muted small">{{ $description }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop
