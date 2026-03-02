@extends('adminlte::page')

@section('title', 'Chart Audit Randomizer')

@section('content_header')
    <h1>Chart Audit Randomizer</h1>
@stop

@section('content')
    @include('chart_audit_randomizers._form', [
        'action' => route('chart-audit-randomizers.store'),
        'method' => null,
        'randomizer' => null,
    ])
@stop
