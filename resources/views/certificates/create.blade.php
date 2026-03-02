@extends('adminlte::page')

@section('title', 'Generate Certificates')

@section('content_header')
    <h1>Generate Certificates</h1>
@stop

@section('content')
    @include('partials.flash')

    @php
        $clientOptions = $clients->sortBy('last_name')->map(function ($client) {
            $dob = $client->date_of_birth
                ? \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y')
                : 'N/A';

            return [
                'id' => $client->id,
                'label' => sprintf(
                    '%s, %s - MRN: %s - DOB: %s',
                    strtoupper($client->last_name),
                    strtoupper($client->first_name),
                    $client->mrn ?? 'N/A',
                    $dob
                ),
            ];
        })->values();

        $typeOptions = $types->map(function ($type) {
            return ['id' => $type->id, 'label' => $type->name];
        })->values();
    @endphp

    <form method="POST" action="{{ route('certificates.store') }}">
        @csrf

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Certificate Details</strong>
                <button type="button" class="btn btn-sm btn-success" id="add-certificate">+ Add Certificate</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0" id="certificates-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 40%">Client</th>
                            <th style="width: 30%">Certificate Type</th>
                            <th style="width: 20%">Graduation Date</th>
                            <th style="width: 10%"></th>
                        </tr>
                    </thead>
                    <tbody id="certificates-body"></tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Generate Certificates
            </button>
            <a href="{{ route('certificates.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const clientOptions = @json($clientOptions);
        const certificateTypes = @json($typeOptions);

        let certificateIndex = 0;

        function addCertificateRow() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select name="certificates[${certificateIndex}][client_id]" class="form-control select2" required>
                        <option value="">Select Client</option>
                        ${clientOptions.map(client => `<option value="${client.id}">${client.label}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <select name="certificates[${certificateIndex}][certificate_type_id]" class="form-control" required>
                        <option value="">Select Type</option>
                        ${certificateTypes.map(type => `<option value="${type.id}">${type.label}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="date" name="certificates[${certificateIndex}][graduation_date]" class="form-control" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-row">×</button>
                </td>
            `;

            document.getElementById('certificates-body').appendChild(row);
            $(row).find('.select2').select2({
                placeholder: "Type to search...",
                allowClear: true
            });

            row.querySelector('.remove-row').addEventListener('click', function () {
                row.remove();
            });

            certificateIndex++;
        }

        document.getElementById('add-certificate').addEventListener('click', addCertificateRow);

        $(document).ready(function() {
            addCertificateRow();
        });
    </script>
@stop
