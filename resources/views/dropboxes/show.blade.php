@extends('adminlte::page')

@section('title', 'Dropbox Details')

@section('content_header')
    <h1>Dropbox Details</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <a href="{{ route('dropboxes.index') }}" class="btn btn-secondary mb-3">Back to Dropbox</a>

    <div class="card">
        <div class="card-body">
            @php
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'info',
                    'denied' => 'danger',
                    'created' => 'success',
                ];
                $statusClass = $statusColors[$dropbox->status] ?? 'secondary';
            @endphp

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="h4 mb-1">{{ strtoupper($dropbox->last_name) }}, {{ strtoupper($dropbox->first_name) }}</h2>
                    <p class="text-muted mb-0">Submitted: {{ optional(optional($dropbox->created_at)->timezone(config('app.timezone')))->format('m/d/Y h:i:s A') ?? 'N/A' }}</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-{{ $statusClass }} text-uppercase">{{ ucfirst($dropbox->status) }}</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="text" class="form-control" value="{{ optional($dropbox->date_of_birth)->format('m/d/Y') ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <input type="text" class="form-control" value="{{ $dropbox->gender ? ucfirst($dropbox->gender) : 'Not Provided' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Source Type</label>
                    <input type="text" class="form-control" value="{{ ucfirst($dropbox->type) }}" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="text" class="form-control" value="{{ $dropbox->email ?? 'Not Provided' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" value="{{ $dropbox->phone ?? 'Not Provided' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Heard About Us</label>
                    <input type="text" class="form-control" value="{{ $dropbox->heard_about_us ?? 'Not Provided' }}" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Drug of Choice</label>
                    <input type="text" class="form-control" value="{{ $dropbox->drug_of_choice }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Use Date</label>
                    <input type="text" class="form-control" value="{{ optional($dropbox->last_use_date)->format('m/d/Y') ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Social Security Number</label>
                    @can('client.view_ssn')
                        <input type="text" class="form-control" value="{{ $dropbox->social_security_number }}" readonly>
                    @else
                        <input type="text" class="form-control" value="Restricted" readonly>
                    @endcan
                </div>
                <div class="col-md-4">
                    <label class="form-label">Medicaid Number</label>
                    @can('client.view_medicaid_id')
                        <input type="text" class="form-control" value="{{ $dropbox->medicaid_number }}" readonly>
                    @else
                        <input type="text" class="form-control" value="Restricted" readonly>
                    @endcan
                </div>

                <div class="col-md-4">
                    <label class="form-label">Returning Client?</label>
                    <input type="text" class="form-control" value="{{ $dropbox->returning_client ? 'Yes' : 'No' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currently in Program?</label>
                    <input type="text" class="form-control" value="{{ $dropbox->currently_in_program ? 'Yes' : 'No' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Document Delivery Method</label>
                    <input type="text" class="form-control" value="{{ $dropbox->document_delivery_method ? ucfirst($dropbox->document_delivery_method) : 'Not Provided' }}" readonly>
                </div>
                @if ($dropbox->currently_in_program)
                    <div class="col-md-4">
                        <label class="form-label">Program Name</label>
                        <input type="text" class="form-control" value="{{ $dropbox->program_name ?? 'Not Provided' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Program Level of Care</label>
                        <input type="text" class="form-control" value="{{ $dropbox->program_level_of_care ?? 'Not Provided' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Program Contact Details</label>
                        <input type="text" class="form-control" value="{{ $dropbox->program_contact_details ?? 'Not Provided' }}" readonly>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">Status Updated</label>
                    <input type="text" class="form-control" value="{{ optional(optional($dropbox->status_updated_at)->timezone(config('app.timezone')))->format('m/d/Y h:i:s A') ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Linked Client</label>
                    @if ($dropbox->client)
                        @can('client.view')
                            <a href="{{ route('clients.show', $dropbox->client) }}" class="btn btn-outline-secondary w-100">{{ strtoupper($dropbox->client->last_name) }}, {{ strtoupper($dropbox->client->first_name) }}</a>
                        @else
                            <input type="text" class="form-control" value="Restricted" readonly>
                        @endcan
                    @else
                        <input type="text" class="form-control" value="Not Linked" readonly>
                    @endif
                </div>

                <div class="col-12">
                    <label class="form-label">Pickup Address &amp; Instructions</label>
                    <textarea class="form-control" rows="2" readonly>{{ $dropbox->pickup_instructions ?? 'Not Provided' }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" rows="3" readonly>{{ $dropbox->notes }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" rows="3" readonly>{{ $dropbox->remarks }}</textarea>
                </div>
            </div>

            <hr>
            <h5>Attachments</h5>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label d-block">Biopsychosocial</label>
                    @if ($dropbox->biopsychosocial_path)
                        <a href="{{ route('dropboxes.download', [$dropbox, 'biopsychosocial']) }}" class="btn btn-outline-primary btn-sm">Download</a>
                    @else
                        <span class="text-muted">Not Provided</span>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Discharge Summary</label>
                    @if ($dropbox->discharge_summary_path)
                        <a href="{{ route('dropboxes.download', [$dropbox, 'discharge']) }}" class="btn btn-outline-primary btn-sm">Download</a>
                    @else
                        <span class="text-muted">Not Provided</span>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Urine History</label>
                    @if (! empty($dropbox->urine_history_paths))
                        <div class="d-flex flex-column gap-1">
                            @foreach ($dropbox->urine_history_paths as $index => $path)
                                <a href="{{ route('dropboxes.download', [$dropbox, 'urine', $index]) }}" class="btn btn-outline-primary btn-sm">Download File {{ $index + 1 }}</a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-muted">Not Provided</span>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Authorization to Release Information</label>
                    @if ($dropbox->roi_document_path)
                        <a href="{{ route('dropboxes.download', [$dropbox, 'roi']) }}" class="btn btn-outline-primary btn-sm">Download ROI</a>
                    @else
                        <span class="text-muted">Not Provided</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
            <div>
                @if ($dropbox->status === 'approved' && ! $dropbox->client)
                    @can('client.create')
                        <a href="{{ route('clients.create', ['dropbox_id' => $dropbox->id]) }}" class="btn btn-success">Create Client</a>
                    @endcan
                @endif
            </div>
            <div class="d-flex gap-2 align-items-center">
                @can('dropbox.delete')
                    <form method="POST" action="{{ route('dropboxes.destroy', $dropbox) }}" data-pin-form="true" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="pin" value="">
                        <button type="submit" class="btn btn-outline-danger" {{ $dropbox->client ? 'disabled' : '' }}>Delete</button>
                    </form>
                @endcan
                @php $canModify = $dropbox->status !== 'created'; @endphp
                <button class="btn btn-outline-success" data-toggle="modal" data-target="#approveModal" {{ $canModify ? '' : 'disabled' }}>Approve</button>
                <button class="btn btn-outline-danger" data-toggle="modal" data-target="#denyModal" {{ $canModify ? '' : 'disabled' }}>Deny</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('dropboxes.status', $dropbox) }}" class="modal-content">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="approved">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Dropbox</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="approve_remarks">Remarks <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="approve_remarks" name="remarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="denyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('dropboxes.status', $dropbox) }}" class="modal-content">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="denied">
                <div class="modal-header">
                    <h5 class="modal-title">Deny Dropbox</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="deny_remarks">Remarks <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deny_remarks" name="remarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Deny</button>
                </div>
            </form>
        </div>
    </div>
@stop
