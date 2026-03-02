@extends('adminlte::page')

@section('title', 'Add New Check')

@section('content_header')
    <h1>Add New Check</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('checks.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label>Check Number</label>
                    <input type="text" name="check_number" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label for="check_attachments">Attachments</label>
                    <input type="file" name="attachments[]" id="check_attachments" class="form-control" multiple>
                </div>
                <div class="form-group col-md-12">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save
        </button>
        <a href="{{ route('checks.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop
