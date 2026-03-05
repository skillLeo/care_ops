@extends('adminlte::page')

@section('title', 'My Profile')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <h1 class="mb-0">My Profile</h1>
        <span class="text-muted" style="font-size:13px;">
            Update your account information and security settings
        </span>
    </div>
@stop

@section('content')

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <strong>Please fix the highlighted fields.</strong>
        </div>
    @endif

    {{-- Premium page styles --}}
    <style>
        .profile-shell{
            max-width: 1100px;
            margin: 0 auto;
        }
        .p-card{
            border: 1px solid #EDF2F7;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.05);
            overflow: hidden;
            background: #fff;
        }
        .p-card-header{
            padding: 18px 22px;
            border-bottom: 1px solid #EDF2F7;
            display:flex;
            align-items:center;
            justify-content:space-between;
            background: #fff;
        }
        .p-card-title{
            font-size: 15px;
            font-weight: 800;
            margin: 0;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .p-card-body{ padding: 22px; }
        .p-muted{ color:#718096; font-size: 13px; }
        .p-divider{ height:1px; background:#EDF2F7; margin: 16px 0; }
        .p-pill{
            display:inline-flex; align-items:center; gap:8px;
            border:1px solid #E2E8F0; padding:6px 10px;
            border-radius: 999px; font-size:12px; color:#4A5568;
            background:#F7FAFC;
        }

        /* Avatar */
        .avatar-wrap{
            width: 88px; height: 88px;
            border-radius: 50%;
            border: 4px solid #E1F2F9;
            background: #F7FAFC;
            overflow: hidden;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
        }
        .avatar-img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display:block;
        }
        .avatar-placeholder{
            width:100%; height:100%;
            display:flex; align-items:center; justify-content:center;
            font-weight: 900;
            font-size: 28px;
            color:#0595D3;
        }

        /* Premium input styling */
        .p-label{
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            color:#718096;
            margin-bottom: 8px;
        }
        .p-input{
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            padding: 10px 14px !important;
            height: auto !important;
            font-size: 13px !important;
        }
        .p-input:focus{
            border-color: #0595D3 !important;
            box-shadow: 0 0 0 4px rgba(5,149,211,0.14) !important;
        }

        /* Buttons */
        .btn-premium{
            border-radius: 12px !important;
            padding: 10px 16px !important;
            font-weight: 800 !important;
            letter-spacing: .2px;
        }
        .btn-cyan{
            background:#0595D3 !important;
            border-color:#0595D3 !important;
            color:#fff !important;
        }
        .btn-cyan:hover{
            background:#047AAD !important;
            border-color:#047AAD !important;
        }
        .btn-purple{
            background:#7252A1 !important;
            border-color:#7252A1 !important;
            color:#fff !important;
        }
        .btn-purple:hover{
            background:#5D4384 !important;
            border-color:#5D4384 !important;
        }
        .btn-soft{
            background:#F7FAFC !important;
            border:1px solid #E2E8F0 !important;
            color:#4A5568 !important;
        }
        .btn-soft:hover{
            background:#EDF2F7 !important;
        }

        /* Compact helper text */
        .p-help{
            font-size: 12px;
            color:#A0AEC0;
            margin-top: 6px;
        }

        /* Section grid */
        .p-grid{
            display:grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }
        @media (min-width: 992px){
            .p-grid{
                grid-template-columns: 1.2fr .8fr;
            }
        }
    </style>

    <div class="profile-shell">

        {{-- TOP PROFILE HEADER --}}
        <div class="p-card mb-3">
            <div class="p-card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:16px;">
                    <div class="d-flex align-items-center" style="gap:16px;">
                        <div class="avatar-wrap">
                            @if($user->avatar)
                                <img id="avatarPreviewTop" class="avatar-img" src="{{ $user->avatarUrl() }}" alt="Avatar">
                            @else
                                <div id="avatarPreviewTop" class="avatar-placeholder">
                                    {{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <div style="font-weight:900;font-size:20px;line-height:1.1;">
                                {{ $user->name }}
                            </div>
                            <div class="p-muted">{{ $user->email }}</div>

                            <div class="mt-2 d-flex flex-wrap" style="gap:8px;">
                                <span class="p-pill">
                                    <i class="fas fa-user-shield" style="color:#0595D3;"></i>
                                    Role ID: {{ $user->role_id }}
                                </span>
                                <span class="p-pill">
                                    <i class="fas fa-camera" style="color:#7252A1;"></i>
                                    JPG/PNG/WEBP • max 2MB
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Avatar upload quick action --}}
                    <div>
                        <button type="button" class="btn btn-soft btn-premium" onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-upload mr-1"></i> Change Photo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-grid">

            {{-- LEFT: PROFILE --}}
            <div class="p-card">
                <div class="p-card-header">
                    <h3 class="p-card-title">
                        <i class="fas fa-id-card" style="color:#0595D3;"></i>
                        Profile Details
                    </h3>
                    <span class="p-muted">Basic info</span>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="p-card-body">

                        {{-- Hidden file input (triggered from button) --}}
                        <input type="file"
                               name="avatar"
                               id="avatarInput"
                               accept="image/*"
                               style="display:none;"
                               class="@error('avatar') is-invalid @enderror">

                        @error('avatar')
                            <div class="text-danger mb-3" style="font-size:13px;font-weight:700;">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="form-group">
                            <div class="p-label">Name</div>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="form-control p-input @error('name') is-invalid @enderror"
                                   placeholder="Your full name">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <div class="p-label">Email</div>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="form-control p-input @error('email') is-invalid @enderror"
                                   placeholder="you@example.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="p-help">This email is used for login and notifications.</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-cyan btn-premium">
                                <i class="fas fa-save mr-1"></i> Save Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- RIGHT: SECURITY --}}
            <div style="display:flex;flex-direction:column;gap:18px;">

                {{-- Password --}}
                <div class="p-card">
                    <div class="p-card-header">
                        <h3 class="p-card-title">
                            <i class="fas fa-lock" style="color:#0595D3;"></i>
                            Password
                        </h3>
                        <span class="p-muted">Security</span>
                    </div>

                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf

                        <div class="p-card-body">
                            <div class="form-group">
                                <div class="p-label">Current Password</div>
                                <input type="password"
                                       name="current_password"
                                       class="form-control p-input @error('current_password') is-invalid @enderror"
                                       placeholder="••••••••">
                                @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <div class="p-label">New Password</div>
                                <input type="password"
                                       name="password"
                                       class="form-control p-input @error('password') is-invalid @enderror"
                                       placeholder="Minimum 8 characters">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <div class="p-label">Confirm New Password</div>
                                <input type="password" name="password_confirmation" class="form-control p-input" placeholder="Repeat new password">
                            </div>

                            <div class="d-flex justify-content-end">
                                <button class="btn btn-purple btn-premium">
                                    <i class="fas fa-shield-alt mr-1"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- PIN --}}
                <div class="p-card">
                    <div class="p-card-header">
                        <h3 class="p-card-title">
                            <i class="fas fa-key" style="color:#7252A1;"></i>
                            PIN
                        </h3>
                        <span class="p-muted">Quick access</span>
                    </div>

                    <form action="{{ route('profile.pin') }}" method="POST">
                        @csrf

                        <div class="p-card-body">
                            @if(!empty($user->pin))
                                <div class="form-group">
                                    <div class="p-label">Current PIN</div>
                                    <input type="password"
                                           name="current_pin"
                                           class="form-control p-input @error('current_pin') is-invalid @enderror"
                                           placeholder="Enter current PIN">
                                    @error('current_pin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div class="form-group">
                                <div class="p-label">New PIN</div>
                                <input type="password"
                                       name="pin"
                                       class="form-control p-input @error('pin') is-invalid @enderror"
                                       placeholder="4 to 8 characters">
                                @error('pin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <div class="p-label">Confirm New PIN</div>
                                <input type="password" name="pin_confirmation" class="form-control p-input" placeholder="Repeat new PIN">
                                <div class="p-help">Your PIN is stored securely (hashed).</div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button class="btn btn-cyan btn-premium">
                                    <i class="fas fa-check mr-1"></i> Update PIN
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>
@stop

@push('js')
<script>
    // Avatar change: update preview + show initial if no image
    const avatarInput = document.getElementById('avatarInput');
    avatarInput?.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;

        const file = this.files[0];
        const reader = new FileReader();

        reader.onload = (e) => {
            const top = document.getElementById('avatarPreviewTop');

            // if it was placeholder div, replace with img
            if (top && top.tagName.toLowerCase() !== 'img') {
                const img = document.createElement('img');
                img.id = 'avatarPreviewTop';
                img.className = 'avatar-img';
                img.alt = 'Avatar';
                img.src = e.target.result;

                top.parentNode.replaceChild(img, top);
            } else if (top) {
                top.src = e.target.result;
            }
        };

        reader.readAsDataURL(file);
    });
</script>
@endpush