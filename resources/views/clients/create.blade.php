@extends('adminlte::page')

@section('title', 'Add Client')

@section('content_header')
    <h1>Add New Client</h1>
@stop

@section('content')
    <a href="{{ route('clients.index') }}" class="btn btn-secondary mb-3">Back to Clients</a>
    <form action="{{ route('clients.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @php
            $dropboxPrefill = $dropbox ?? null;
            $dropboxDob = $dropboxPrefill ? optional($dropboxPrefill->date_of_birth)->format('Y-m-d') : null;
            $selectedGender = old('gender', $gender ?? ($dropboxPrefill->gender ?? null));
        @endphp
        @if ($dropboxPrefill)
            <input type="hidden" name="dropbox_id" value="{{ old('dropbox_id', $dropboxPrefill->id ?? '') }}">
            <div class="alert alert-info">
                Prefilling information from Dropbox submission on
                {{ optional(optional($dropboxPrefill->created_at)->timezone(config('app.timezone')))->format('m/d/Y h:i:s A') }}.
                <a href="{{ route('dropboxes.show', $dropboxPrefill) }}" class="alert-link" target="_blank">View Dropbox</a>.
            </div>
        @endif
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" class="form-control" value="{{ old('first_name', $dropboxPrefill->first_name ?? '') }}" required>
                <p>Confirm with Carelon</p>
            </div>
            <div class="col-md-6 form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" class="form-control" value="{{ old('last_name', $dropboxPrefill->last_name ?? '') }}" required>
                <p>Confirm with Carelon</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="{{ old('date_of_birth', $dropboxDob) }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="gender">Gender</label>
                <select name="gender" id="gender" class="form-control" {{ isset($gender) ? 'disabled' : '' }}
                    required>

                    <option value="" disabled {{ isset($gender) ? '' : 'selected' }}>Select Gender</option>

                    <option value="male" {{ $selectedGender == 'male' ? 'selected' : '' }}>
                        Male
                    </option>

                    <option value="female" {{ $selectedGender == 'female' ? 'selected' : '' }}>
                        Female
                    </option>

                </select>

                <!-- Hidden input to submit the gender if the field is disabled -->
                @if (isset($gender))
                    <input type="hidden" name="gender" value="{{ $gender }}">
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $dropboxPrefill->email ?? '') }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone" class="form-control" pattern="\d{3}-\d{3}-\d{4}" placeholder="123-456-7890" value="{{ old('phone', $dropboxPrefill->phone ?? '') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="guest">Guest Client</label>
                <select name="guest" id="guest" class="form-control" required>
                    <option value=""></option>
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label for="eligibility">Eligibility</label>
                <select name="eligibility" id="eligibility" class="form-control" required>
                    <option value="" selected>Select eligibility</option>
                    <option value="eligible" {{ old('eligibility') === 'eligible' ? 'selected' : '' }}>Eligible</option>
                    <option value="not_eligible" {{ old('eligibility') === 'not_eligible' ? 'selected' : '' }}>Not Eligible</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="redetermination_date">Redetermination Date</label>
                <input type="date" name="redetermination_date" id="redetermination_date" class="form-control" value="{{ old('redetermination_date') }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="redetermination_remarks">Redetermination Remarks</label>
                <input type="text" name="redetermination_remarks" id="redetermination_remarks" class="form-control" value="{{ old('redetermination_remarks') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 form-group">
                <label for="apartment_id">House / Apartment</label>
                <select name="apartment_id" id="apartment_id" class="form-control">
                    <option value="" selected>Select House / Apartment</option>
                    @foreach($houses as $house)
                        @foreach($house->apartments as $apt)
                            <option value="{{ $apt->id }}" {{ old('apartment_id') == $apt->id ? 'selected' : '' }}>
                                {{ $house->house_name }} - {{ $apt->apartment_number }} ({{ $apt->clients()->active()->count() }}/{{ $apt->capacity ?? 0 }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="mrn">MRN</label>
                <input type="number" name="mrn" id="mrn" class="form-control" required
                    value="{{ $nextMrn }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="ssn">SSN</label>
                @can('client.view_ssn')
                    <input type="text" name="ssn" id="ssn" class="form-control" value="{{ old('ssn', $dropboxPrefill->social_security_number ?? '') }}" required>
                    <p>Write 'Not Reported' if not available</p>
                @else
                    <input type="text" id="ssn" class="form-control" value="***" disabled>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="medicaid_id">Medicaid ID</label>
                @can('client.view_medicaid_id')
                    <input type="text" name="medicaid_id" id="medicaid_id" class="form-control" value="{{ old('medicaid_id', $dropboxPrefill->medicaid_number ?? '') }}" required>
                    <p>Write 'Not Reported' if not available</p>
                @else
                    <input type="text" id="medicaid_id" class="form-control" value="***" disabled>
                @endcan
            </div>
            <div class="col-md-6 form-group">
                <label for="carelon_id">Carelon ID</label>
                @can('client.view_carelon_id')
                    <input type="text" name="carelon_id" id="carelon_id" class="form-control" required>
                    <p>Write 'Not Reported' if not available</p>
                @else
                    <input type="text" id="carelon_id" class="form-control" value="***" disabled>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="starting_date">Starting Date</label>
                <input type="date" name="starting_date" id="starting_date" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="level_of_care">Level of Care</label>
                <select name="level_of_care" id="level_of_care" class="form-control"
                    {{ isset($levelOfCareId) ? 'disabled' : '' }} required>

                    <option value="" disabled {{ isset($levelOfCareId) ? '' : 'selected' }}>Select Level of Care</option>

                    @foreach($levelOfCares as $level)
                        <option value="{{ $level->id }}"
                            {{ (old('level_of_care') ?? ($levelOfCareId ?? '')) == $level->id ? 'selected' : '' }}>
                            {{ $level->display_name }}
                        </option>
                    @endforeach
                </select>

                <!-- Hidden input to submit the group if the field is disabled -->
                @if (isset($levelOfCareId))
                    <input type="hidden" name="level_of_care" value="{{ $levelOfCareId }}">
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="counselor_id">Counselor</label>
                <select name="counselor_id" id="counselor_id" class="form-control" required>
                    <option value="" selected>Select a Counselor</option>
                    @foreach($counselors as $counselor)
                        <option value="{{ $counselor->id }}">
                            {{ $counselor->name }} ({{ $counselor->levelOfCare?->display_name ?? '-' }} - {{ $counselor->clients_count }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label for="peer_id">Peer</label>
                <select name="peer_id" id="peer_id" class="form-control">
                    <option value="" selected>Select a Peer</option>
                    @foreach($peers as $peer)
                        <option value="{{ $peer->id }}">
                            {{ $peer->name }} ({{ $peer->levelOfCare?->display_name ?? '-' }} - {{ $peer->peer_clients_count }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="client_group_id">Client Group</label>
                <select name="client_group_id" id="client_group_id" class="form-control" required>
                    <option value="" selected>Select a Group</option>
                    @foreach($clientGroups as $group)
                        <option value="{{ $group->id }}" data-level-of-care-id="{{ $group->level_of_care_id }}" {{ old('client_group_id') == $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label for="peer_group_id">Peer Group</label>
                <select name="peer_group_id" id="peer_group_id" class="form-control">
                    <option value="" selected>Select a Peer Group</option>
                    @foreach($peerGroups as $peerGroup)
                        <option value="{{ $peerGroup->id }}" {{ old('peer_group_id') == $peerGroup->id ? 'selected' : '' }}>
                            {{ $peerGroup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>


        <div class="row">
            <div class="col-md-6 form-group">
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" class="form-control" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $dropboxPrefill->notes ?? '') }}</textarea>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label for="evs_data">EVS Data</label>
                <textarea name="evs_data" id="evs_data" class="form-control" rows="8">{{ old('evs_data') }}</textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="profile_photo">Profile Photo</label>
                <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">



                <button type="button" class="btn btn-primary mt-2" onclick="startWebcam()">Use Webcam</button>
                <button type="button" class="btn btn-danger mt-2" onclick="stopWebcam()">Stop Webcam</button>
                <video id="webcam" style="width: 320px; height: auto;" autoplay hidden></video>
                <canvas id="canvas" hidden></canvas>
                <button type="button" class="btn btn-success mt-2" id="capture-btn" hidden
                    onclick="capturePhoto()">Capture Photo</button>

                <div id="crop-container" style="display: none;">
                    <img id="crop-preview" style="max-width: 320px; height: auto;">
                    <button type="button" class="btn btn-warning mt-2" onclick="cropImage()">Crop & Save</button>
                </div>

                <img id="preview" src="{{ old('profile_photo') ? asset('storage/' . old('profile_photo')) : '' }}"
                    class="img-thumbnail mt-2"
                    style="max-width: 200px; height: auto; display: {{ old('profile_photo') ? 'block' : 'none' }};">
                <input type="hidden" name="captured_photo" id="captured_photo">
            </div>
            <div class="col-md-6 form-group">
                <label for="camera_select">Choose Camera</label>
                <select id="camera_select" class="form-control"></select>
            </div>
        </div>


        <div class="row">
            <div class="col-md-6">


                <div class="form-group">

                </div>

            </div>


        </div>



        <button type="submit" class="btn btn-primary mt-3">Add Client</button>
    </form>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <script>
        let selectedDeviceId = null;
        let cropper = null;

        navigator.mediaDevices.enumerateDevices().then(devices => {
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            const select = document.getElementById('camera_select');
            videoDevices.forEach(device => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Camera ${select.length + 1}`;
                select.appendChild(option);
            });
            selectedDeviceId = videoDevices[0]?.deviceId || null;
        });

        function startWebcam() {
            const webcam = document.getElementById('webcam');
            const captureBtn = document.getElementById('capture-btn');
            const selectedCamera = document.getElementById('camera_select').value;

            navigator.mediaDevices.getUserMedia({
                    video: {
                        deviceId: selectedCamera ? {
                            exact: selectedCamera
                        } : undefined
                    }
                })
                .then(stream => {
                    webcam.srcObject = stream;
                    webcam.hidden = false;
                    captureBtn.hidden = false;
                })
                .catch(error => console.error("Error accessing webcam:", error));
        }

        function stopWebcam() {
            const webcam = document.getElementById('webcam');
            if (webcam.srcObject) {
                let tracks = webcam.srcObject.getTracks();
                tracks.forEach(track => track.stop());
                webcam.srcObject = null;
            }
            webcam.hidden = true;
        }

        function capturePhoto() {
            const webcam = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            const cropContainer = document.getElementById('crop-container');
            const cropPreview = document.getElementById('crop-preview');

            canvas.width = webcam.videoWidth;
            canvas.height = webcam.videoHeight;
            context.drawImage(webcam, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/png');

            cropPreview.src = imageData;
            cropContainer.style.display = 'block';
            if (cropper) {
                cropper.destroy();
            }
            cropper = new Cropper(cropPreview, {});

            // Stop webcam after capture
            stopWebcam();
            document.getElementById('capture-btn').hidden = true;
        }

        function cropImage() {
            if (cropper) {
                const croppedCanvas = cropper.getCroppedCanvas();
                const imageData = croppedCanvas.toDataURL('image/png');

                document.getElementById('preview').src = imageData;
                document.getElementById('preview').style.display = 'block';
                document.getElementById('captured_photo').value = imageData;
                document.getElementById('crop-container').style.display = 'none';

                // Reset cropper for new captures
                cropper.destroy();
                cropper = null;
            }
        }


        const levelOfCareSelect = document.getElementById('level_of_care');
        const clientGroupSelect = document.getElementById('client_group_id');
        const clientGroupOptions = clientGroupSelect ? Array.from(clientGroupSelect.options) : [];

        function filterClientGroupsByLoc() {
            if (!levelOfCareSelect || !clientGroupSelect) {
                return;
            }

            const selectedLoc = String(levelOfCareSelect.value || '');
            const currentValue = clientGroupSelect.value;

            clientGroupOptions.forEach((option) => {
                const optionLoc = option.dataset.levelOfCareId;
                if (!optionLoc) {
                    option.hidden = false;
                    return;
                }

                option.hidden = optionLoc !== selectedLoc;
            });

            const stillValid = Array.from(clientGroupSelect.options).some((option) => option.value === currentValue && !option.hidden);
            clientGroupSelect.value = stillValid ? currentValue : '';
        }

        levelOfCareSelect?.addEventListener('change', filterClientGroupsByLoc);
        filterClientGroupsByLoc();

        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = x[1] + (x[2] ? '-' + x[2] : '') + (x[3] ? '-' + x[3] : '');
        });
    </script>
@endsection
