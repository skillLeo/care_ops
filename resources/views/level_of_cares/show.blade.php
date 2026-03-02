@extends('adminlte::page')

@section('title', 'View Level of Care')

@section('content_header')
    <h1>Level of Care Details</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p><strong>Level of Care:</strong> {{ $levelOfCare->level_of_care }}</p>
            <p><strong>Display Name:</strong> {{ $levelOfCare->display_name }}</p>
            <p><strong>Update Note Days:</strong> {{ $levelOfCare->update_note_days ?? '—' }}</p>
            <p><strong>Units:</strong> {{ $levelOfCare->units ?? '—' }}</p>
            <p><strong>Auth Days:</strong> {{ $levelOfCare->auth_days ?? '—' }}</p>

            <a href="{{ route('level-of-cares.edit', $levelOfCare) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('level-of-cares.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
@stop
