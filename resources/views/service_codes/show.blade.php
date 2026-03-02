@extends('adminlte::page')

@section('title', 'Service Code Details')

@section('content_header')
    <h1>Service Code Details</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <strong>Levels of Care:</strong>
                {{ $serviceCode->levelsOfCare->pluck('display_name')->implode(', ') ?: 'N/A' }}
            </div>
            <div class="mb-3">
                <strong>Service Code:</strong> {{ $serviceCode->service_code }}
            </div>
            <div class="mb-3">
                <strong>Friendly Name:</strong> {{ $serviceCode->friendly_name ?? 'N/A' }}
            </div>
            <div class="mb-3">
                <strong>Length Data:</strong> {{ $serviceCode->length_data ?? 'N/A' }}
            </div>
            <div class="mb-3">
                <strong>Service Type:</strong> {{ $serviceCode->service_type ?? 'N/A' }}
            </div>

            <h5>Price Date Ranges</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Starting Date</th>
                        <th>Ending Date</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($serviceCode->prices as $price)
                        <tr>
                            <td>{{ $price->starting_date?->format('Y-m-d') ?? 'N/A' }}</td>
                            <td>{{ $price->ending_date?->format('Y-m-d') ?? 'N/A' }}</td>
                            <td>{{ number_format($price->price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No price ranges found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <a href="{{ route('service-codes.index') }}" class="btn btn-secondary">Back</a>
            @can('service_code.edit')
                <a href="{{ route('service-codes.edit', $serviceCode) }}" class="btn btn-warning">Edit</a>
            @endcan
        </div>
    </div>
@stop
