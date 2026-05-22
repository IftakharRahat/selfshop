@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Basicinfo
@endsection

<style>
    .basicinfo-wrapper {
        max-width: 1400px;
        margin: 0 auto;
    }
    .basicinfo-wrapper .admin-card-body {
        padding: 20px;
    }
    .basicinfo-wrapper .form-group {
        margin-bottom: 16px;
    }
    .basicinfo-wrapper .form-group:last-child {
        margin-bottom: 0;
    }
    .basicinfo-wrapper .form-group > label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: var(--admin-text, #1e293b);
        margin-bottom: 5px;
    }
    .basicinfo-wrapper .form-group > label .text-muted {
        font-weight: 400;
        font-size: 12px;
    }

    /* Current asset preview */
    .current-asset {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 10px;
        padding: 10px 14px;
        background: var(--admin-bg, #f8fafc);
        border: 1px solid var(--admin-border, #e2e8f0);
        border-radius: 8px;
    }
    .current-asset .asset-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--admin-text-muted, #94a3b8);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }
    .current-asset img {
        max-height: 40px;
        border-radius: 4px;
    }

    /* Submit button */
    .basicinfo-wrapper .btn-save-section {
        padding: 10px 32px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        background: var(--admin-primary, #2d2a5d);
        border: none;
        color: #fff;
        transition: all 0.2s ease;
    }
    .basicinfo-wrapper .btn-save-section:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(45, 42, 93, 0.3);
    }

    /* Section icon in header */
    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .section-icon.settings { background: #eef2ff; color: #4f46e5; }
    .section-icon.analytics { background: #fef3c7; color: #d97706; }
    .section-icon.social { background: #dbeafe; color: #2563eb; }
    .section-icon.shipping { background: #d1fae5; color: #059669; }
    .section-icon.mobile { background: #fce7f3; color: #db2777; }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="basicinfo-wrapper">

        {{-- Breadcrumb --}}
        <div class="pagetitle mb-3">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Basic Information</li>
                </ol>
            </nav>
        </div>

        <div class="row">

            {{-- ============ LEFT COLUMN ============ --}}
            <div class="col-xl-6 mb-4">

                {{-- Settings Update --}}
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon settings"><i class="bi bi-gear"></i></div>
                            <h6 class="admin-card-title mb-0">General Settings</h6>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <form action="{{ route('admin.basicinfos.update', $webinfo->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" class="form-control" name="email" value="{{ $webinfo->email }}"
                                    placeholder="name@example.com">
                            </div>
                            <div class="form-group">
                                <label>WhatsApp Number</label>
                                <input type="text" class="form-control" name="wp_number"
                                    value="{{ $webinfo->wp_number }}" placeholder="WhatsApp number">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone One</label>
                                        <input type="text" class="form-control" name="phone_one"
                                            value="{{ $webinfo->phone_one }}" placeholder="Primary phone">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone Two</label>
                                        <input type="text" class="form-control" name="phone_two"
                                            value="{{ $webinfo->phone_two }}" placeholder="Secondary phone">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Office Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Office address">{{ $webinfo->address }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Referral Bonus Amount (Tk) <span class="text-muted" style="font-weight:400; font-size:12px;">— fixed amount given to referrer when referred user subscribes</span></label>
                                <input type="number" min="0" step="0.01" class="form-control" name="bonus_percent"
                                    value="{{ $webinfo->bonus_percent }}" placeholder="e.g. 100">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Logo</label>
                                        <input class="form-control" name="logo" type="file">
                                        @if($webinfo->logo)
                                        <div class="current-asset">
                                            <span class="asset-label">Current:</span>
                                            <img src="{{ asset(preg_replace('#^public/#', '', $webinfo->logo ?? '')) }}" alt="Logo">
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Favicon</label>
                                        <input class="form-control" name="fav_icon" type="file">
                                        @if($webinfo->fav_icon)
                                        <div class="current-asset">
                                            <span class="asset-label">Current:</span>
                                            <img src="{{ asset(preg_replace('#^public/#', '', $webinfo->fav_icon ?? '')) }}" alt="Favicon">
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-save-section">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon social"><i class="bi bi-share"></i></div>
                            <h6 class="admin-card-title mb-0">Social Links</h6>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <form action="{{ url('/admin/basicinfo/update', $webinfo->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label><i class="bi bi-whatsapp me-1"></i> WhatsApp Link</label>
                                <input type="text" class="form-control" name="wp_link"
                                    value="{{ $webinfo->wp_link }}" placeholder="https://wa.me/...">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-messenger me-1"></i> Messenger Link</label>
                                <input type="text" class="form-control" name="messanger_link"
                                    value="{{ $webinfo->messanger_link }}" placeholder="https://m.me/...">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-facebook me-1"></i> Facebook</label>
                                <input type="text" class="form-control" name="facebook"
                                    value="{{ $webinfo->facebook }}" placeholder="https://www.facebook.com/">
                            </div>
                            <div class="form-group" hidden>
                                <label>Twitter</label>
                                <input type="text" class="form-control" name="twitter"
                                    value="{{ $webinfo->twitter }}" placeholder="https://www.twitter.com/">
                            </div>
                            <div class="form-group" hidden>
                                <label>Google</label>
                                <input type="text" class="form-control" name="google"
                                    value="{{ $webinfo->google }}" placeholder="https://google.com">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-tiktok me-1"></i> TikTok</label>
                                <input type="text" class="form-control" name="rss"
                                    value="{{ $webinfo->rss }}" placeholder="https://www.tiktok.com/">
                            </div>
                            <div class="form-group" hidden>
                                <label>Pinterest</label>
                                <input type="text" class="form-control" name="pinterest"
                                    value="{{ $webinfo->pinterest }}" placeholder="https://www.pinterest.com/">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-instagram me-1"></i> Instagram</label>
                                <input type="text" class="form-control" name="linkedin"
                                    value="{{ $webinfo->linkedin }}" placeholder="https://www.instagram.com/">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-youtube me-1"></i> YouTube</label>
                                <input type="text" class="form-control" name="youtube"
                                    value="{{ $webinfo->youtube }}" placeholder="https://www.youtube.com/">
                            </div>

                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-save-section">Save Social Links</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ============ RIGHT COLUMN ============ --}}
            <div class="col-xl-6 mb-4">

                {{-- Pixel & Analytics --}}
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon analytics"><i class="bi bi-graph-up"></i></div>
                            <h6 class="admin-card-title mb-0">Pixel & Analytics</h6>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <form action="{{ url('/admin/pixel/analytics', $webinfo->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Invoice Footer Text</label>
                                <textarea class="form-control" name="invoice_footer" rows="2" placeholder="Text shown at the bottom of invoices">{{ $webinfo->invoice_footer }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Facebook Pixel ID <span class="text-muted" style="font-weight:400; font-size:12px;">— just the numeric ID from Facebook Ads Manager</span></label>
                                <input type="text" class="form-control" name="facebook_pixel_id"
                                    value="{{ $webinfo->facebook_pixel_id }}" placeholder="e.g. 123456789012345">
                            </div>
                            <div class="form-group">
                                <label>GTM Container ID <span class="text-muted" style="font-weight:400; font-size:12px;">— from Google Tag Manager</span></label>
                                <input type="text" class="form-control" name="gtm_id"
                                    value="{{ $webinfo->gtm_id }}" placeholder="e.g. GTM-XXXXXXX">
                            </div>
                            <div class="form-group">
                                <label>Google Analytics ID <span class="text-muted" style="font-weight:400; font-size:12px;">— GA4 Measurement ID (gtag.js)</span></label>
                                <input type="text" class="form-control" name="google_analytics_id"
                                    value="{{ $webinfo->google_analytics_id }}" placeholder="e.g. G-XXXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label>Marquee Text</label>
                                <textarea class="form-control" name="marquee_text" id="marquee_text" rows="2" placeholder="Scrolling announcement text">{{ $webinfo->marquee_text }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Chatbox Script <span class="text-muted">(paste full script)</span></label>
                                <textarea class="form-control" name="chat_box" id="chat_box" rows="3" placeholder="<!-- Chat widget script -->">{{ $webinfo->chat_box }}</textarea>
                            </div>

                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-save-section">Save Analytics</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Shipping & Payment Information --}}
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon shipping"><i class="bi bi-truck"></i></div>
                            <h6 class="admin-card-title mb-0">Shipping & Payment Info</h6>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <form action="{{ route('admin.shipping.update', $webinfo->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @method('PUT')
                            @csrf

                            <p style="font-size: 13px; font-weight: 600; color: var(--admin-text-muted, #64748b); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Payment Numbers</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>bKash</label>
                                        <input type="text" class="form-control" name="b_one"
                                            value="{{ $webinfo->b_one }}" placeholder="bKash number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nagad</label>
                                        <input type="text" class="form-control" name="b_two"
                                            value="{{ $webinfo->b_two }}" placeholder="Nagad number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Rocket</label>
                                        <input type="text" class="form-control" name="b_three"
                                            value="{{ $webinfo->b_three }}" placeholder="Rocket number">
                                    </div>
                                </div>
                            </div>

                            <hr style="border-color: var(--admin-border, #e2e8f0); margin: 16px 0;">

                            <p style="font-size: 13px; font-weight: 600; color: var(--admin-text-muted, #64748b); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Delivery Charges <span style="font-weight: 400; text-transform: none; letter-spacing: normal; font-size: 11px; color: #94a3b8;">— auto-calculated based on supplier & customer city</span></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Same City <span class="text-muted" style="font-weight:400; font-size:11px;">— supplier & customer in same city</span></label>
                                        <input type="text" class="form-control" name="inside_dhaka_charge"
                                            value="{{ $webinfo->inside_dhaka_charge }}" id="inside_dhaka_charge"
                                            placeholder="৳ 60">
                                    </div>
                                </div>
                                <div class="col-md-4" hidden>
                                    <div class="form-group">
                                        <label>Surrounding Dhaka</label>
                                        <input type="text" class="form-control" name="near_dhaka_charge"
                                            value="{{ $webinfo->near_dhaka_charge }}" id="near_dhaka_charge"
                                            placeholder="৳ 0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Inter-City <span class="text-muted" style="font-weight:400; font-size:11px;">— supplier & customer in different cities</span></label>
                                        <input type="text" class="form-control" name="outside_dhaka_charge"
                                            value="{{ $webinfo->outside_dhaka_charge }}" id="outside_dhaka_charge"
                                            placeholder="৳ 130">
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden fields preserved --}}
                            <div hidden>
                                <input type="text" class="form-control" name="insie_dhaka" value="{{ $webinfo->insie_dhaka }}" id="insie_dhaka">
                                <input type="text" class="form-control" name="outside_dhaka" value="{{ $webinfo->outside_dhaka }}" id="outside_dhaka">
                                <input type="text" class="form-control" name="contact" value="{{ $webinfo->contact }}" id="contact">
                                <input type="text" class="form-control" name="refund_rule" value="{{ $webinfo->refund_rule }}" id="refund_rule">
                                <select name="cash_on_delivery" id="cash_on_delivery">
                                    @if ($webinfo->cash_on_delivery == 'ON')
                                        <option value="ON" selected>ON</option>
                                        <option value="OFF">OFF</option>
                                    @else
                                        <option value="ON">ON</option>
                                        <option value="OFF" selected>OFF</option>
                                    @endif
                                </select>
                            </div>

                            <hr style="border-color: var(--admin-border, #e2e8f0); margin: 16px 0;">

                            <p style="font-size: 13px; font-weight: 600; color: var(--admin-text-muted, #64748b); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">SEO / Meta</p>
                            <div class="form-group">
                                <label>Meta Title</label>
                                <input type="text" class="form-control" name="title"
                                    value="{{ $webinfo->title }}" placeholder="Site meta title">
                            </div>
                            <div class="form-group">
                                <label>Meta Description</label>
                                <textarea class="form-control" name="meta_description" id="meta_description" rows="2" placeholder="Site meta description">{{ $webinfo->meta_description }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Keywords</label>
                                <textarea class="form-control" name="meta_keyword" id="meta_keyword" rows="2" placeholder="Comma-separated keywords">{{ $webinfo->meta_keyword }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Image</label>
                                <input class="form-control" name="meta_image" type="file">
                                @if($webinfo->meta_image)
                                <div class="current-asset">
                                    <span class="asset-label">Current:</span>
                                    <img src="{{ asset(preg_replace('#^public/#', '', $webinfo->meta_image ?? '')) }}" alt="Meta Image">
                                </div>
                                @endif
                            </div>

                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-save-section">Save Shipping Info</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- App Version Management --}}
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon mobile"><i class="bi bi-phone"></i></div>
                            <h6 class="admin-card-title mb-0">App Version Management</h6>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <form action="{{ route('admin.app-version.update', $webinfo->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>Android Version Code <span class="text-muted">(integer — must match the versionCode in your Play Store build)</span></label>
                                <input type="number" class="form-control" name="android_app_version_code"
                                    value="{{ $webinfo->android_app_version_code ?? 1 }}" min="1" placeholder="e.g. 1">
                            </div>
                            <div class="form-group">
                                <label>Play Store URL <span class="text-muted">(Google Play listing link)</span></label>
                                <input type="url" class="form-control" name="android_play_store_url"
                                    value="{{ $webinfo->android_play_store_url }}" placeholder="https://play.google.com/store/apps/details?id=com.selfshop.app">
                            </div>
                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-save-section">Save App Version</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

@endsection
