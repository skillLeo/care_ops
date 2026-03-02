@extends('adminlte::page')

@section('title', 'Edit Check')

@section('content_header')
    <h1>Edit Check #{{ $check->check_number }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('checks.update', $check) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label>Check Number</label>
                    <input type="text" name="check_number" class="form-control" value="{{ $check->check_number }}" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ $check->payment_date }}">
                </div>
                <div class="form-group col-md-4">
                    <label for="check_attachments">Attachments</label>
                    <input type="file" name="attachments[]" id="check_attachments" class="form-control" multiple>
                    @if (! empty($check->attachments))
                        <div class="mt-2 d-flex flex-column gap-2">
                            <div class="text-muted small">Existing attachments (check to remove)</div>
                            @foreach ($check->attachments as $file)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $file }}" id="remove_attachment_{{ $loop->index }}">
                                        <label class="form-check-label" for="remove_attachment_{{ $loop->index }}">
                                            Remove {{ $file }}
                                        </label>
                                    </div>
                                    <a href="{{ route('checks.attachments.download', [$check, $file]) }}" class="btn btn-sm btn-outline-info">Download</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="form-group col-md-12">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ $check->remarks }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update
        </button>
        <a href="{{ route('checks.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop
