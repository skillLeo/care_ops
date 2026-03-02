@extends('adminlte::page')

@section('title', 'Edit Chart Audit Randomizer')

@section('content_header')
    <h1>Edit Chart Audit Randomizer</h1>
@stop

@section('content')
    @include('chart_audit_randomizers._form', [
        'action' => route('chart-audit-randomizers.update', $randomizer),
        'method' => 'PUT',
        'randomizer' => $randomizer,
    ])
@stop
