@extends('adminlte::page')

@section('title', 'Certificates')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Certificates</h1>
        <div class="d-flex gap-2">
            @can('certificate-type.view')
                <a href="{{ route('certificate-types.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-cogs"></i> Manage Certificate Types
                </a>
            @endcan
            @can('certificate.create')
                <a href="{{ route('certificates.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Generate Certificates
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-body">
            @can('certificate.download')
                <div class="d-flex justify-content-end mb-3">
                    <button type="submit" form="bulk-download-form" class="btn btn-outline-secondary" disabled id="bulk-download-button">
                        <i class="fas fa-file-archive"></i> Download Selected (ZIP)
                    </button>
                </div>
            @endcan
            <form method="POST" action="{{ route('certificates.bulk-download') }}" id="bulk-download-form">
                @csrf
                <table id="certificatesTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <input type="checkbox" id="select-all-certificates">
                            </th>
                            <th>Client</th>
                            <th>Certificate Type</th>
                            <th>Graduation Date</th>
                            <th>Issued At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td class="text-center">
                                    @can('certificate.download')
                                        <input type="checkbox" name="certificate_ids[]" value="{{ $certificate->id }}" class="certificate-checkbox">
                                    @endcan
                                </td>
                                <td>{{ $certificate->client->last_name }}, {{ $certificate->client->first_name }}</td>
                                <td>{{ $certificate->type_label }}</td>
                                <td data-order="{{ $certificate->graduation_date?->format('Y-m-d') }}">
                                    {{ $certificate->graduation_date?->format('m/d/Y') }}
                                </td>
                                <td data-order="{{ $certificate->issued_at?->format('Y-m-d H:i:s') }}">
                                    {{ $certificate->issued_at?->format('m/d/Y') ?? '—' }}
                                </td>
                                <td>
                                    @can('certificate.download')
                                        <a href="{{ route('certificates.download', $certificate) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    @endcan
                                    @can('certificate.regenerate')
                                        <form action="{{ route('certificates.regenerate', $certificate) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="fas fa-sync-alt"></i> Regenerate
                                            </button>
                                        </form>
                                    @endcan
                                    @can('certificate.edit')
                                        <a href="{{ route('certificates.edit', $certificate) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    @endcan
                                    @can('certificate.delete')
                                        <form action="{{ route('certificates.destroy', $certificate) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No certificates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#certificatesTable').DataTable({
                pageLength: 50,
                order: [
                    [3, 'desc'],
                    [1, 'asc']
                ],
                columnDefs: [{
                        orderable: false,
                        targets: [0, 5]
                    },
                ],
            });

            const bulkButton = document.getElementById('bulk-download-button');
            const selectAll = document.getElementById('select-all-certificates');
            const checkboxes = document.querySelectorAll('.certificate-checkbox');

            const refreshBulkButton = () => {
                if (!bulkButton) {
                    return;
                }
                const anyChecked = Array.from(checkboxes).some((checkbox) => checkbox.checked);
                bulkButton.disabled = !anyChecked;
            };

            if (selectAll) {
                selectAll.addEventListener('change', (event) => {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = event.target.checked;
                    });
                    refreshBulkButton();
                });
            }

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', refreshBulkButton);
            });
        });
    </script>
@stop
