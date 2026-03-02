@extends('adminlte::page')

@section('title', 'Email Templates')

@section('content_header')
    <h1>Email Templates</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Subject</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->key }}</td>
                            <td>{{ $template->subject }}</td>
                            <td class="text-end">
                                <a href="{{ route('email-templates.edit', $template) }}" class="btn btn-primary btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
