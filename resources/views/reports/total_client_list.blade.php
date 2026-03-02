@extends('adminlte::page')

@section('title', 'Total Client List')

@section('content_header')
    <h1>Total Client List</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.totalClientList') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="group_id" class="form-label">Group</label>
                    <select name="group" id="group_id" class="form-control" required>
                        <option value="">Select a group</option>
                        @foreach ($groupOptions as $groupOption)
                            <option value="{{ $groupOption->level_of_care }}" {{ old('group', $selectedGroup) === $groupOption->level_of_care ? 'selected' : '' }}>
                                {{ $groupOption->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="house_type" class="form-label">House Type</label>
                    <select name="house_type" id="house_type" class="form-control">
                        <option value="">All Houses</option>
                        <option value="grove" {{ old('house_type', $houseType) === 'grove' ? 'selected' : '' }}>Grove</option>
                        <option value="non-grove" {{ old('house_type', $houseType) === 'non-grove' ? 'selected' : '' }}>Non-Grove</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="from" class="form-label">Start Date</label>
                    <input type="date" name="from" id="from" class="form-control"
                           value="{{ old('from', $from) }}" required>
                </div>
                <div class="col-md-2">
                    <label for="to" class="form-label">End Date</label>
                    <input type="date" name="to" id="to" class="form-control"
                           value="{{ old('to', $to) }}" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @php
        $hasFilters = $selectedGroup && $from && $to;
    @endphp

    @if ($hasFilters)
        <div class="card">
            <div class="card-body">
                @if ($clients->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="totalClientsTable">
                            <thead>
                                <tr>
                                    <th>First Last</th>
                                    <th>Last, First</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clients as $client)
                                    <tr>
                                        <td>{{ $client->first_name }} {{ $client->last_name }}</td>
                                        <td>{{ $client->last_name }}, {{ $client->first_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No clients found for the selected criteria.</p>
                @endif
            </div>
        </div>
    @endif
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            const table = $('#totalClientsTable');
            if (table.length) {
                table.DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    order: []
                });
            }
        });
    </script>
@stop
