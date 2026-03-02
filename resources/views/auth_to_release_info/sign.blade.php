@extends('adminlte::page')

@section('title', 'Sign Authorization')

@section('content_header')
    <h1>Sign Authorization for {{ $client->first_name }} {{ $client->last_name }} ({{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }})</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('auth-to-release-info.sign', [$client->id, $medicalContact->id]) }}" method="POST" onsubmit="saveSignature()">
            @csrf

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
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);

        function saveSignature() {
            if (signaturePad.isEmpty()) {
                alert("Please provide a signature.");
                event.preventDefault();
                return;
            }
            document.getElementById('signature').value = signaturePad.toDataURL('image/png');
        }

        function clearSignature() {
            signaturePad.clear();
        }

        function resizeCanvas(canvasElement, padInstance) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvasElement.width = canvasElement.offsetWidth * ratio;
            canvasElement.height = canvasElement.offsetHeight * ratio;
            canvasElement.getContext("2d").scale(ratio, ratio);
            padInstance.clear();
        }

        function resizeAllCanvases() {
            resizeCanvas(canvas, signaturePad);
        }

        window.addEventListener('resize', resizeAllCanvases);
        resizeAllCanvases();
    </script>
@stop
