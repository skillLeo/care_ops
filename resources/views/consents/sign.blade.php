@extends('adminlte::page')

@section('title', 'Sign Consent Form')

@section('content_header')
    <h1>Sign Consent {{ $client->first_name }} {{ $client->last_name }} ({{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }})</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('consents.sign', $consent->id) }}" method="POST" onsubmit="saveSignature()">
            @csrf

            <!-- Medical Contacts Section -->
            <div class="form-group">
                <h4>Medical Contacts</h4>
                <div class="row">
                    @foreach($medicalContacts as $contact)
                        <div class="col-md-6">
                            <div class="border rounded p-2 mb-2">
                                <strong>{{ $contact->name }}</strong><br>
                                <small>{{ $contact->email }}</small><br>
                                <input type="checkbox" name="generate_medical_contacts[]" value="{{ $contact->id }}" class="generate-checkbox" data-email-checkbox="email-{{ $contact->id }}"> Generate PDF
                                <input type="checkbox" name="email_medical_contacts[]" value="{{ $contact->id }}" id="email-{{ $contact->id }}" class="email-checkbox" disabled> Email
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr>

            <!-- Signature Section -->
            <div class="form-group">
                <div><label><strong>Signature:</strong></label></div>
                <div class="border rounded p-1" style="background-color: #f8f9fa; display: inline-block;">
                    <canvas id="signature-pad" class="signature-pad" style="width: 400px; height: 100px;"></canvas>
                </div>
                <input type="hidden" name="signature" id="signature">
                <div><button type="button" class="btn btn-secondary" onclick="clearSignature()">Clear Signature</button></div>
            </div>

            <hr>

            <div class="form-group">
                <div><label><strong>Initials:</strong></label></div>
                <div class="border rounded p-1" style="background-color: #f8f9fa; display: inline-block;">
                    <canvas id="initials-pad" class="initials-pad" style="width: 200px; height: 100px;"></canvas>
                </div>
                <input type="hidden" name="initials" id="initials">
                <div><button type="button" class="btn btn-secondary" onclick="clearInitials()">Clear Initials</button></div>
            </div>

            <hr>

            <!-- Date Section -->
            <div class="form-group d-flex align-items-center">
                <label for="signed_date" class="mb-0 mr-2"><strong>Date:</strong></label>
                <input type="date" name="signed_date" id="signed_date" class="form-control w-auto"
                       value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
            </div>

            <!-- Buttons -->
            <div class="form-group mt-3">

                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>
@stop

@section('css')
    <style>
        .signature-pad {
            border: 2px solid #ced4da;
            border-radius: 5px;
        }

        .initials-pad {
            border: 2px solid #ced4da;
            border-radius: 5px;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
                // Signature Pad
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);

        // Initials Pad
        const initialsCanvas = document.getElementById('initials-pad');
        const initialsPad = new SignaturePad(initialsCanvas);

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.generate-checkbox').forEach(function(generateCheckbox) {
                generateCheckbox.addEventListener('change', function() {
                    let emailCheckbox = document.getElementById(this.getAttribute('data-email-checkbox'));
                    emailCheckbox.disabled = !this.checked;
                    if (!this.checked) {
                        emailCheckbox.checked = false;
                    }
                });
            });
        });

        function saveSignature() {
            if (signaturePad.isEmpty()) {
                alert("Please provide a signature.");
                event.preventDefault();
                return;
            }
            if (initialsPad.isEmpty()) {
                alert("Please provide your initials.");
                event.preventDefault();
                return;
            }
            document.getElementById('signature').value = signaturePad.toDataURL('image/png');
            document.getElementById('initials').value = initialsPad.toDataURL('image/png');
        }

        function clearSignature() {
            signaturePad.clear();
        }

        function clearInitials() {
            initialsPad.clear();
        }

        // Resize function for both canvases
        function resizeCanvas(canvasElement, padInstance) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvasElement.width = canvasElement.offsetWidth * ratio;
            canvasElement.height = canvasElement.offsetHeight * ratio;
            canvasElement.getContext("2d").scale(ratio, ratio);
            padInstance.clear();
        }

        // Ensure both canvases resize correctly
        function resizeAllCanvases() {
            resizeCanvas(canvas, signaturePad);
            resizeCanvas(initialsCanvas, initialsPad);
        }

        window.addEventListener('resize', resizeAllCanvases);
        resizeAllCanvases(); // Call on load to set initial sizes
    </script>
@stop
