@extends('adminlte::page')

@section('title', 'Edit UA Randomizer')

@section('content_header')
    <h1>Edit UA Randomizer</h1>
@stop

@section('content')
    @include('ua_randomizers._form', [
        'action' => route('ua-randomizers.update', $randomizer),
        'method' => 'PUT',
        'randomizer' => $randomizer,
    ])
@stop
