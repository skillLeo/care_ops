@extends('adminlte::page')

@section('title', 'View User')

@section('content_header')
    <h1>View User: {{ $user->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Short Name:</strong> {{ $user->short_name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Roles:</strong> {{ $user->roles->pluck('display_name')->join(', ') ?: 'No Roles' }}</p>
            <p><strong>Level of Care:</strong> {{ $user->levelOfCare?->display_name ?? '-' }}</p>
        </div>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to Users</a>
@stop
