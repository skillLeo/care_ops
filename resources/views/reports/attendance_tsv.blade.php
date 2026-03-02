@extends('adminlte::page')

@section('title', 'Attendance TSV')

@section('content_header')
    <h1>Attendance TSV</h1>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('reports.attendanceTsv') }}" class="mb-4">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control"
                    value="{{ old('start_date', $startDate) }}" required>
                @error('start_date')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control"
                    value="{{ old('end_date', $endDate) }}" required>
                @error('end_date')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="house_type" class="form-label">House</label>
                <select id="house_type" name="house_type" class="form-control">
                    <option value="all" {{ ($houseType ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="grove" {{ ($houseType ?? 'all') === 'grove' ? 'selected' : '' }}>Grove</option>
                    <option value="non-grove" {{ ($houseType ?? 'all') === 'non-grove' ? 'selected' : '' }}>Non-Grove</option>
                    <option value="housed" {{ ($houseType ?? 'all') === 'housed' ? 'selected' : '' }}>Housed</option>
                    <option value="non-housed" {{ ($houseType ?? 'all') === 'non-housed' ? 'selected' : '' }}>Non-Housed</option>
                </select>
                @error('house_type')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="level_of_care" class="form-label">Level of Care</label>
                <select id="level_of_care" name="level_of_care" class="form-control">
                    <option value="" {{ empty($levelOfCare) ? 'selected' : '' }}>All</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->level_of_care }}" {{ $levelOfCare === $level->level_of_care ? 'selected' : '' }}>{{ $level->display_name }}</option>
                    @endforeach
                </select>
                @error('level_of_care')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-12 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="present_only" name="present_only" value="1"
                        {{ $presentOnly ? 'checked' : '' }}>
                    <label class="form-check-label" for="present_only">Present Only</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    @if ($tableRows->isNotEmpty())
        <div class="d-flex justify-content-end mb-3">
            <form method="POST" action="{{ route('reports.attendanceTsv.download') }}">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="house_type" value="{{ $houseType }}">
                <input type="hidden" name="level_of_care" value="{{ $levelOfCare }}">
                <input type="hidden" name="present_only" value="{{ $presentOnly ? 1 : 0 }}">
                <button type="submit" class="btn btn-success">Download TSV ZIP</button>
            </form>
        </div>

        <table class="table table-bordered" id="attendanceTsvTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="text-center">MRN</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Units</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tableRows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-center">{{ $row['mrn'] }}</td>
                        <td class="text-center">{{ $row['present'] ? 'Yes' : 'No' }}</td>
                        <td class="text-center">{{ $row['units'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($startDate && $endDate)
        <div class="alert alert-info">No attendance records found for the selected filters.</div>
    @endif
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            const table = $('#attendanceTsvTable');

            if (table.length) {
                table.DataTable({
                    order: [
                        [0, 'asc']
                    ]
                });
            }
        });
    </script>
@stop
