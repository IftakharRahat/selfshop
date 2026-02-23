@extends('backend.master')

@section('maincontent')
@section('title')
    My - Profile
@endsection

<style>
    .profile-header-card {
        background: linear-gradient(135deg, var(--admin-primary, #2d2a5d) 0%, #4a3f8f 100%);
        border-radius: 12px;
        padding: 32px;
        color: #fff;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .profile-header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
    }

    .profile-header-card::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.03);
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        color: #fff;
    }

    .profile-header-info h4 {
        color: #fff !important;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .profile-header-info p {
        color: rgba(255, 255, 255, 0.7) !important;
        margin-bottom: 0;
        font-size: 14px;
    }

    .profile-header-info .badge-role {
        background: rgba(255, 255, 255, 0.15);
        color: #fff !important;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        backdrop-filter: blur(10px);
    }

    .profile-section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--admin-primary, #2d2a5d);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #f0eef9;
    }

    .profile-field-group {
        margin-bottom: 20px;
    }

    .profile-field-group label {
        font-size: 13px;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 6px;
    }

    .profile-field-group .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .profile-field-group .form-control:focus {
        border-color: var(--admin-primary, #2d2a5d);
        box-shadow: 0 0 0 3px rgba(45, 42, 93, 0.1);
    }

    .profile-field-group .form-control:disabled {
        background: #f8fafc;
        color: #475569;
        cursor: not-allowed;
    }

    .document-preview {
        width: 100%;
        max-width: 160px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .document-preview:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .document-upload-card {
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        text-align: center;
        transition: border-color 0.2s, background 0.2s;
    }

    .document-upload-card:hover {
        border-color: var(--admin-primary, #2d2a5d);
        background: #f0eef9;
    }

    .document-upload-card label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .document-upload-card .form-control {
        border: none;
        background: transparent;
        padding: 4px;
        font-size: 13px;
    }

    .btn-profile-save {
        background: var(--admin-primary, #2d2a5d);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 32px;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
    }

    .btn-profile-save:hover {
        background: #3d3780;
        color: #fff;
        box-shadow: 0 4px 12px rgba(45, 42, 93, 0.3);
        transform: translateY(-1px);
    }

    .btn-profile-save:active {
        transform: translateY(0);
    }

    .password-section {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 20px;
    }

    .password-section .profile-section-title {
        color: #92400e;
        border-bottom-color: #fde68a;
    }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">My Profile</li>
            </ol>
        </nav>
    </div>

    <form name="form" action="{{ url('admin/update/profile') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Profile Header --}}
        <div class="profile-header-card">
            <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 1;">
                @if (isset(Auth::guard('admin')->user()->shop_icon) && Auth::guard('admin')->user()->shop_icon)
                    <img src="{{ asset(Auth::guard('admin')->user()->shop_icon) }}" alt="Shop Icon" class="profile-avatar">
                @else
                    <div class="profile-avatar">
                        {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}
                    </div>
                @endif
                <div class="profile-header-info">
                    <h4>{{ Auth::guard('admin')->user()->name }}</h4>
                    <p>{{ Auth::guard('admin')->user()->email }}</p>
                    <span class="badge-role mt-1 d-inline-block">
                        <i class="bi bi-shield-check me-1"></i>
                        {{ Auth::guard('admin')->user()->shop_name ?? 'Administrator' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Left Column --}}
            <div class="col-lg-7">
                {{-- Owner Information --}}
                <div class="admin-content-card mb-4">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-person me-2"></i>Owner Information</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label>Owner Name</label>
                                    <input type="text" class="form-control" name="name" value="{{ Auth::guard('admin')->user()->name }}" placeholder="Enter owner name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" name="email" value="{{ Auth::guard('admin')->user()->email }}" placeholder="Enter email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label>Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="{{ Auth::guard('admin')->user()->phone }}" placeholder="Enter phone number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shop Details --}}
                <div class="admin-content-card mb-4">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-shop me-2"></i>Shop Details</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label>Shop Name</label>
                                    <input type="text" class="form-control" name="shop_name" id="shop_name"
                                        placeholder="Enter shop name" value="{{ Auth::guard('admin')->user()->shop_name }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label>Shop Contact</label>
                                    <input type="text" class="form-control" name="shop_contact" id="shop_contact"
                                        placeholder="Enter shop contact" value="{{ Auth::guard('admin')->user()->shop_contact }}">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="profile-field-group">
                                    <label>Shop Address</label>
                                    <textarea class="form-control" name="shop_address" id="shop_address"
                                        placeholder="Enter shop address" rows="3">{{ Auth::guard('admin')->user()->shop_address }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label>Shop Licence Number</label>
                                    <input type="text" class="form-control" name="shop_licence_number" id="shop_licence_number"
                                        placeholder="Enter licence number" value="{{ Auth::guard('admin')->user()->shop_licence_number }}">
                                </div>
                            </div>
                            <div class="col-md-6 d-none">
                                <div class="profile-field-group">
                                    <label>Delivery Charge</label>
                                    <input type="text" class="form-control" name="delivery_charge" id="delivery_charge"
                                        placeholder="Enter delivery charge" value="{{ Auth::guard('admin')->user()->delivery_charge }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-5">
                {{-- Documents & Images --}}
                <div class="admin-content-card mb-4">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-file-earmark-image me-2"></i>Documents & Images</h6>
                    </div>
                    <div class="admin-card-body">
                        {{-- Shop Icon --}}
                        <div class="mb-4">
                            <div class="profile-section-title">Shop Icon</div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                @if (isset(Auth::guard('admin')->user()->shop_icon) && Auth::guard('admin')->user()->shop_icon)
                                    <img src="{{ asset(Auth::guard('admin')->user()->shop_icon) }}" alt="Shop Icon" class="document-preview">
                                @else
                                    <img src="{{ asset('public/webview/assets/images/cart.jpg') }}" alt="Default" class="document-preview">
                                @endif
                            </div>
                            <div class="document-upload-card">
                                <label><i class="bi bi-cloud-arrow-up me-1"></i> Upload New Icon</label>
                                <input type="file" name="shop_icon" id="shop_icon" class="form-control">
                            </div>
                        </div>

                        {{-- Trade Licence --}}
                        <div class="mb-4">
                            <div class="profile-section-title">Trade Licence</div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                @if (isset(Auth::guard('admin')->user()->trade_licence) && Auth::guard('admin')->user()->trade_licence)
                                    <img src="{{ asset(Auth::guard('admin')->user()->trade_licence) }}" alt="Trade Licence" class="document-preview">
                                @else
                                    <img src="{{ asset('public/webview/assets/images/cart.jpg') }}" alt="Default" class="document-preview">
                                @endif
                            </div>
                            <div class="document-upload-card">
                                <label><i class="bi bi-cloud-arrow-up me-1"></i> Upload Trade Licence</label>
                                <input type="file" name="trade_licence" id="trade_licence" class="form-control">
                            </div>
                        </div>

                        {{-- National ID --}}
                        <div class="mb-0">
                            <div class="profile-section-title">National ID</div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                @if (isset(Auth::guard('admin')->user()->national_id) && Auth::guard('admin')->user()->national_id)
                                    <img src="{{ asset(Auth::guard('admin')->user()->national_id) }}" alt="National ID" class="document-preview">
                                @else
                                    <img src="{{ asset('public/webview/assets/images/cart.jpg') }}" alt="Default" class="document-preview">
                                @endif
                            </div>
                            <div class="document-upload-card">
                                <label><i class="bi bi-cloud-arrow-up me-1"></i> Upload National ID</label>
                                <input type="file" name="national_id" id="national_id" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Change Password --}}
                <div class="admin-content-card mb-4">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-shield-lock me-2"></i>Change Password</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="password-section">
                            <div class="profile-field-group">
                                <label>Old Password</label>
                                <input type="password" class="form-control" name="old_password"
                                    autocomplete="old-password" placeholder="Enter current password">
                            </div>
                            <div class="profile-field-group mb-0">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="password"
                                    autocomplete="new-password" placeholder="Enter new password">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="d-flex justify-content-end mb-4">
            <button type="submit" name="btn" class="btn btn-profile-save">
                <i class="bi bi-check-circle me-1"></i> Update Profile
            </button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {});
</script>

@endsection
