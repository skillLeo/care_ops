@extends('adminlte::page')

@section('title', 'UA Randomizer')

@section('content_header')
    <h1>UA Randomizer</h1>
@stop

@section('content')
    @include('ua_randomizers._form', [
        'action' => route('ua-randomizers.store'),
        'method' => null,
        'randomizer' => null,
    ])
@stop
