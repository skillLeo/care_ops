@extends('adminlte::page')

@section('title', 'Edit Service Code')

@section('content_header')
    <h1>Edit Service Code</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('service-codes.update', $serviceCode) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Levels of Care</label>
                    <div class="border rounded p-2">
                        @php
                            $selectedLevels = old('level_of_care_ids', $serviceCode->levelsOfCare->pluck('id')->all());
                        @endphp
                        @foreach ($levels as $level)
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="level_of_care_ids[]"
                                    id="level_of_care_{{ $level->id }}"
                                    value="{{ $level->id }}"
                                    {{ in_array($level->id, $selectedLevels, true) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="level_of_care_{{ $level->id }}">
                                    {{ $level->display_name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label for="service_code">Service Code</label>
                    <input type="text" name="service_code" id="service_code" class="form-control" value="{{ old('service_code', $serviceCode->service_code) }}" required>
                </div>

                <div class="form-group">
                    <label for="friendly_name">Friendly Name</label>
                    <input type="text" name="friendly_name" id="friendly_name" class="form-control" value="{{ old('friendly_name', $serviceCode->friendly_name) }}">
                </div>

                <div class="form-group">
                    <label for="length_data">Length Data</label>
                    <input type="text" name="length_data" id="length_data" class="form-control" value="{{ old('length_data', $serviceCode->length_data) }}">
                </div>

                <div class="form-group">
                    <label for="service_type">Service Type</label>
                    <input type="text" name="service_type" id="service_type" class="form-control" value="{{ old('service_type', $serviceCode->service_type) }}">
                </div>

                @php
                    $priceAmounts = old('price_amount');
                    $priceStarts = old('price_starting_date');
                    $priceEnds = old('price_ending_date');
                    $priceRows = $priceAmounts !== null
                        ? count($priceAmounts)
                        : max($serviceCode->prices->count(), 1);
                @endphp

                <div class="form-group">
                    <label>Price Date Ranges</label>
                    <table class="table table-bordered" id="priceTable">
                        <thead>
                            <tr>
                                <th>Starting Date</th>
                                <th>Ending Date</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($index = 0; $index < $priceRows; $index++)
                                @php
                                    $price = $priceAmounts[$index] ?? optional($serviceCode->prices[$index])->price;
                                    $start = $priceStarts[$index] ?? optional($serviceCode->prices[$index])->starting_date?->format('Y-m-d');
                                    $end = $priceEnds[$index] ?? optional($serviceCode->prices[$index])->ending_date?->format('Y-m-d');
                                @endphp
                                <tr>
                                    <td>
                                        <input type="date" name="price_starting_date[]" class="form-control" value="{{ $start }}">
                                    </td>
                                    <td>
                                        <input type="date" name="price_ending_date[]" class="form-control" value="{{ $end }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="price_amount[]" class="form-control" value="{{ $price }}" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success btn-sm insert-row">Insert Above</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-row">Delete</button>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="addPriceRow">Add New Row</button>
                </div>

                <button type="submit" class="btn btn-success">Save</button>
                <a href="{{ route('service-codes.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.querySelector('#priceTable tbody');

        document.querySelector('#addPriceRow').addEventListener('click', function () {
            addNewRow();
        });

        table.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-row')) {
                event.target.closest('tr').remove();
            }
            if (event.target.classList.contains('insert-row')) {
                const row = event.target.closest('tr');
                row.insertAdjacentHTML('beforebegin', getRowHtml());
            }
        });

        function addNewRow() {
            table.insertAdjacentHTML('beforeend', getRowHtml());
        }

        function getRowHtml() {
            return `
                <tr>
                    <td>
                        <input type="date" name="price_starting_date[]" class="form-control">
                    </td>
                    <td>
                        <input type="date" name="price_ending_date[]" class="form-control">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="price_amount[]" class="form-control" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-success btn-sm insert-row">Insert Above</button>
                        <button type="button" class="btn btn-danger btn-sm delete-row">Delete</button>
                    </td>
                </tr>
            `;
        }
    });
</script>
@stop
