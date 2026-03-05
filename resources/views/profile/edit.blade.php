@extends('adminlte::page')

@section('title', 'My Profile')

@section('content_header')
@stop

@section('content')
<style>
    .profile-wrap{ min-height: calc(100vh - 120px); padding: 10px 0 30px; }
    .profile-center{ max-width: 980px; margin: 0 auto; }
    .profile-logo{ display:flex; justify-content:center; align-items:center; margin: 10px 0 14px; }
    .profile-logo img{ height: 56px; width: auto; object-fit: contain; }

    .profile-card{
        background:#fff; border-radius:18px; border:1px solid var(--border-color);
        box-shadow: var(--shadow-md); overflow:hidden;
    }
    .profile-card-topline{ height:4px; background: var(--primary); }
    .profile-card-header{
        padding:18px 22px; border-bottom:1px solid var(--border-color);
        display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .profile-card-header h2{
        margin:0; font-size:20px; font-weight:800; color:var(--text-primary); letter-spacing:-0.3px;
    }
    .profile-sub{ margin:2px 0 0; color:var(--text-secondary); font-size:13px; font-weight:500; }

    .profile-grid{
        display:grid; grid-template-columns: 1fr 1fr; gap:18px;
        padding:18px 22px 22px;
    }
    @media (max-width: 991px){ .profile-grid{ grid-template-columns:1fr; } }

    .section-card{
        border:1px solid var(--border-color); border-radius:16px; background:#fff;
        box-shadow: var(--shadow-sm); overflow:hidden;
    }
    .section-head{
        padding:14px 16px; border-bottom:1px solid var(--border-color);
        display:flex; align-items:center; gap:10px;
        font-weight:800; color:var(--text-primary); font-size:14px;
    }
    .section-body{ padding:16px; }
    .section-foot{
        padding:12px 16px; border-top:1px solid var(--border-color);
        display:flex; justify-content:flex-end; gap:10px; background:#fff;
    }

    .form-group label{
        text-transform:none !important;
        letter-spacing:0 !important;
        font-size:12px !important;
    }

    .avatar-wrap{
        display:flex; align-items:center; gap:14px;
        padding: 12px; border:1px solid var(--border-color); border-radius:14px;
        background:#fff;
    }
    .avatar-img{
        width:64px; height:64px; border-radius:50%;
        object-fit:cover; border:2px solid var(--border-color);
        background:#F4F7FA;
    }
    .avatar-meta{ flex:1; min-width:0; }
    .avatar-name{ font-weight:800; color:var(--text-primary); }
    .avatar-email{ font-size:12px; color:var(--text-secondary); }
</style>

<div class="profile-wrap">
    <div class="profile-center">

    
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Please fix the highlighted fields.
            </div>
        @endif

        <div class="profile-card">
            <div class="profile-card-topline"></div>

            <div class="profile-card-header">
                <div>
                    <h2>My Profile</h2>
                    <div class="profile-sub">Update your account information, photo, and password.</div>
                </div>
            </div>

            <div class="profile-grid">
                {{-- Profile Info + Avatar --}}
                <div class="section-card">
                    <div class="section-head">
                        <i class="fas fa-id-card text-primary"></i>
                        Profile Information
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="section-body">
                            {{-- Avatar row --}}
                            <div class="avatar-wrap mb-3">
                                <img id="avatarPreview" class="avatar-img" src="{{ $user->avatarUrl() }}" alt="Avatar">
                                <div class="avatar-meta">
                                    <div class="avatar-name">{{ $user->name }}</div>
                                    <div class="avatar-email">{{ $user->email }}</div>
                                    <div class="text-muted" style="font-size:12px;">JPG/PNG/WEBP • max 2MB</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Profile Photo</label>
                                <div class="custom-file">
                                    <input type="file"
                                           name="avatar"
                                           id="avatarInput"
                                           class="custom-file-input @error('avatar') is-invalid @enderror"
                                           accept="image/*">
                                    <label class="custom-file-label" for="avatarInput">Choose image</label>
                                </div>
                                @error('avatar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" name="name"
                                       value="{{ old('name', $user->name) }}"
                                       class="form-control @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label>Email *</label>
                                <input type="email" name="email"
                                       value="{{ old('email', $user->email) }}"
                                       class="form-control @error('email') is-invalid @enderror">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="section-foot">
                            <button class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Password --}}
                <div class="section-card">
                    <div class="section-head">
                        <i class="fas fa-lock text-primary"></i>
                        Change Password
                    </div>

                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf

                        <div class="section-body">
                            <div class="form-group">
                                <label>Current Password *</label>
                                <input type="password" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror">
                                @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>New Password *</label>
                                <input type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label>Confirm Password *</label>
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>

                            <small class="text-muted d-block mt-2">
                                Password must be at least 8 characters.
                            </small>
                        </div>

                        <div class="section-foot">
                            <button class="btn btn-secondary-brand">
                                <i class="fas fa-key mr-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('avatarInput');
    const preview = document.getElementById('avatarPreview');
    if (!input || !preview) return;

    input.addEventListener('change', function (e) {
        const file = e.target.files && e.target.files[0];
        if (!file) return;

        // update file label
        const label = e.target.nextElementSibling;
        if (label) label.textContent = file.name;

        // instant preview
        const url = URL.createObjectURL(file);
        preview.src = url;
    });
});
</script>
@stop