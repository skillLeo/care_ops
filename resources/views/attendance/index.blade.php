<!-- resources/views/attendance/index.blade.php -->
@extends('adminlte::page')

@section('title', 'Attendance Management')

@section('content_header')
    <h1>Select Client</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clients as $client)
                <tr>
                    <td>{{ $client->last_name }}, {{ $client->first_name }}</td>
                    <td>
                        <a href="{{ route('attendances.show', $client) }}" class="btn btn-primary">
                            Enter Attendance
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop
