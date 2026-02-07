@extends('backend.master')

@section('maincontent')

@section('subcss')
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/css/dataTables.checkboxes.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
<style>
    #to_account_number::placeholder {
        color: white;
        opacity: 1; /* Firefox */
    }
</style>
<div class="px-4 pt-4 container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
    {{-- edit payment icon --}}
    <div class="modal fade" id="editmainFrd" tabindex="-1">
        <div class="modal-dialog">
            <div class="rounded modal-content bg-secondary h-100">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: red;">Edit Withdrew Request </h5>
                    <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                        aria-label="Close" style="background-color: red !important;color: white !important;opacity: 1;"></button>
                </div>
                <div class="modal-body">

                    <form name="form" id="EditMenu" enctype="multipart/form-data" class="from-prevent-multiple-submits">
                        @csrf
                        <div class="row justify-content-center align-items-center">
                            <div class="col-md-10 justify-content-center align-items-center">
                                <div class="mt-3 form-group">
                                    <label for="" class="m-0">আপনি কতো টাকা নিতে চাচ্ছেন ?</label>
                                    <div class="d-flex">
                                        <input type="text" style="background: #FFF3DE !important;border:none;border-radius: 6px" class="form-control" name="withdrew_amount" id="withdrew_amount" placeholder="এখানে টাকার পরিমান লিখুন" required>
                                        <button style="padding: 0px 10px;border: 2px solid #D6CFFF;font-weight: bold;margin-left: -8px;border-radius: 6px;background: #D6CFFF;">৳</button>
                                    </div>
                                </div>

                                <div class="mt-3 form-group">
                                    <label for="" class="m-0">উত্তোলনের মাধ্যম সিলেক্ট করুন</label>
                                    <div class="d-flex justify-content-between">
                                        @forelse (App\Models\Paymenttype::where('status','Active')->get() as $pay)
                                            <input hidden type="radio" id="pay{{ $pay->id }}" name="paymenttype_id" value="{{ $pay->id }}">
                                            <label for="pay{{ $pay->id }}" class="copays" id="copay{{ $pay->id }}" onclick="setpaymenttype({{ $pay->id }},'{{ $pay->paymentTypeName }}')" style="border:2px solid;border-radius:6px;">
                                                <img src="{{ asset($pay->icon) }}" alt="" width="100px;">
                                            </label>
                                        @empty

                                        @endforelse
                                    </div>
                                </div>
                                <input type="text" name="withdrew_id" id="withdrew_id" hidden>
                                <div class="mt-3 form-group">
                                    <label for="" class="m-0">একাউন্ট নাম্বার মিলিয়ে নিন</label>
                                    <div class="d-flex justify-content-between">
                                        <button id="account" style="padding: 0px 10px;border: 2px solid #FAE2FE !important;font-weight: bold;margin-left: -8px;border-radius: 6px;background: #FAE2FE;margin-right:30px;"></button>
                                        <input type="text" style="background: #E2136E !important;border:none;border-radius: 6px;color: white;" class="form-control" name="to_account_number" id="to_account_number" placeholder="এখানে নম্বর লিখুন" required>
                                    </div>
                                </div>
                                <br>
                                <div class="form-group">
                                    <label for=""> Choose Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Cancel">Cancel</option>
                                    </select>
                                </div>
                                <br>
                                <div class="d-flex w-100 justify-content-center">
                                    <button type="submit" style="width: 292px;font-size: 26px;height: 59px;color: #fff;font-weight: bold;background: #14BF7D;border-radius: 30px;"
                                        class="btn btn-primary from-prevent-multiple-submits">
                                        <i class="spinner fa fa-spinner fa-spin"></i>
                                        আপডেট করুন
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div><!-- End popup Modal-->

    {{-- //table section for category --}}
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="pt-4 card-body" style="text-align: center;">
                        @if (\Session::has('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-1"></i>
                                {{ \Session::get('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
                        <h4>Withdrew List Of User</h4>
                        <div class="buttonsec">
                            <a href="{{ url('withdrew/All') }}" class="text-white btn btn-sm" style="background:#2A74B8;border:1px solid #2A74B8;">All</a>
                            <a href="{{ url('withdrew/Pending') }}" class="text-white btn btn-sm" style="background:#EB762A;border:1px solid #EB762A;">Pending</a>
                            <a href="{{ url('withdrew/Paid') }}" class="text-white btn btn-sm" style="background:#14BF7D;border:1px solid #14BF7D;">Paid</a>
                            <a href="{{ url('withdrew/Cancel') }}" class="text-white btn btn-sm" style="background:#613EEA;border:1px solid #613EEA;">Cancel</a>
                        </div>
                        <!-- Table with stripped rows -->
                        <div class="table-responsive">
                            <table class="table mb-0 table-centered table-borderless table-hover" id="productrqinfo"
                                width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th></th>
                                        <th>User</th>
                                        <th>Cash Out Option</th>
                                        <th>Account Info</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <!-- End Table with stripped rows -->

                    </div>
                </div>

            </div>
        </div>
    </section>

<style>
    /* Premium iPhone-style mobile footer */
    .bottom-navbar {
        background: #fdf0f6;
        backdrop-filter: saturate(180%) blur(20px);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 12px 0 10px 0;
        height: 75px;
        box-shadow: 0 -4px 25px rgba(0, 0, 0, 0.2);
        border-radius: 25px 25px 0 0;
        overflow: hidden;
    }

    .nav-icons-container {
        display: flex;
        justify-content: space-around;
        align-items: center;
        height: 100%;
        padding: 0 10px;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        position: relative;
        padding: 8px 12px;
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 65px;
        flex: 1;
    }

    .nav-item:hover, .nav-item.active {
        background: rgb(230, 0, 76);
    }

    .nav-icon-container {
        position: relative;
        width: 28px;
        height: 28px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-icon {
        font-size: 20px;
        color: #8E8E93;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 2;
    }

    .nav-icon-bg {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
    }

    .nav-item:hover .nav-icon-bg,
    .nav-item.active .nav-icon-bg {
        background: rgba(255, 255, 255, 0.1);
        width: 40px;
        height: 40px;
    }

    .nav-label {
        color: #8E8E93;
        font-size: 10px;
        font-weight: 500;
        opacity: 0.9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.2px;
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
    }

    .nav-item:hover .nav-icon,
    .nav-item.active .nav-icon {
        color: #FFFFFF;
        transform: scale(1.1);
    }

    .nav-item:hover .nav-label,
    .nav-item.active .nav-label {
        color: #FFFFFF;
        opacity: 1;
        transform: translateY(-1px);
    }

    /* Special cart button styling (iPhone-like floating) */
    .cart-nav-item {
        margin-top: -25px;
        background: linear-gradient(135deg, #E5005F 0%, #5856D6 100%);
        border-radius: 50%;
        padding: 18px;
        box-shadow: 
            0 6px 20px rgba(0, 122, 255, 0.4),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        min-width: auto;
        width: 48px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cart-nav-item .nav-icon-container {
        margin-bottom: 0;
        width: 24px;
        height: 24px;
    }

    .cart-nav-item .nav-icon {
        font-size: 18px;
        color: #FFFFFF;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }

    .cart-nav-item .nav-label {
        position: absolute;
        bottom: -20px;
        font-size: 9px;
        color: #8E8E93;
        background: transparent;
        opacity: 0.9;
    }

    .cart-nav-item:hover .nav-label {
        color: #FFFFFF;
    }

    /* Cart badge (iOS style) */
    .cart-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #FF3B30;
        color: white;
        font-size: 11px;
        font-weight: 600;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(29, 29, 31, 0.95);
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
        z-index: 3;
    }

    /* Active state indicators */
    .nav-item.active::before {
        content: '';
        position: absolute;
        top: 4px;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #E5005F;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .nav-item.active::before {
        opacity: 1;
    }

    /* Home icon active state */
    .nav-item.active[href*="/"] .nav-icon {
        color: #E5005F;
    }

    /* Dashboard icon active state */
    .nav-item.active[href*="dashboard"] .nav-icon {
        color: #34C759;
    }

    /* Profile/Login icon active state */
    .nav-item.active[href*="login"] .nav-icon,
    .nav-item.active[onclick*="profile"] .nav-icon {
        color: #FF9500;
    }

    /* Menu icon active state */
    .nav-item[onclick*="openNav"]:hover .nav-icon,
    .nav-item[onclick*="openNav"]:active .nav-icon {
        color: #AF52DE;
    }

    /* Touch feedback */
    .nav-item:active .nav-icon-container {
        transform: scale(0.95);
    }

    /* Glass morphism effect for the navbar */
    .bottom-navbar::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(
            to bottom,
            rgba(255, 255, 255, 0.05) 0%,
            rgba(255, 255, 255, 0.02) 100%
        );
        z-index: -1;
    }


    /* Responsive adjustments */
    @media (max-width: 360px) {
        .bottom-navbar {
            height: 70px;
            padding: 10px 0 8px 0;
        }
        
        .nav-item {
            min-width: 55px;
            padding: 6px 8px;
        }
        
        .nav-icon {
            font-size: 18px;
        }
        
        .nav-label {
            font-size: 9px;
        }
        
        .cart-nav-item {
            width: 60px;
            height: 60px;
            padding: 16px;
        }
    }

    /* iPhone X and later safe area */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .bottom-navbar {
            padding-bottom: calc(10px + env(safe-area-inset-bottom));
        }
    }
</style>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- iPhone-style mobile footer -->
<div class="bottom-navbar b-block d-lg-none">
    <div class="container">
        <div class="nav-icons-container">
            <!-- Home -->
            <a href="{{url('/')}}" class="nav-item {{ Request::url() == env('APP_URL').'/' ? 'active' : '' }}">
                <div class="nav-icon-container">
                    <div class="nav-icon-bg"></div>
                    <i class="nav-icon fas fa-home"></i>
                </div>
                <span class="nav-label">Home</span>
            </a>

            <!-- Menu -->
            <a href="javascript:void(0);" onclick="openNav()" class="nav-item">
                <div class="nav-icon-container">
                    <div class="nav-icon-bg"></div>
                    <i class="nav-icon fas fa-bars"></i>
                </div>
                <span class="nav-label">Menu</span>
            </a>

            <!-- Cart (Floating iPhone-style) -->
            <a href="{{ url('checkout') }}" class="nav-item cart-nav-item">
                 <div class="nav-icon-container">
                    <i class="nav-icon fas fa-shopping-bag"></i>
                </div>
                <span class="nav-label">Cart</span>
            </a>

            <!-- Dashboard -->
            <a href="{{ url('user/dashboard') }}" class="nav-item {{ Request::url() == env('APP_URL').'/user/dashboard' ? 'active' : '' }}">
                <div class="nav-icon-container">
                    <div class="nav-icon-bg"></div>
                    <i class="nav-icon fas fa-chart-line"></i>
                </div>
                <span class="nav-label">Dashboard</span>
            </a>

            <!-- Profile/Login -->
            @if(Auth::id())
                <a href="javascript:void(0);" onclick="openprofile()" class="nav-item">
                    <div class="nav-icon-container">
                        <div class="nav-icon-bg"></div>
                        <i class="nav-icon fas fa-user-circle"></i>
                    </div>
                    <span class="nav-label">Profile</span>
                </a>
            @else
                <a href="{{url('login')}}#vendor" class="nav-item {{ Request::url() == env('APP_URL').'/login' ? 'active' : '' }}">
                    <div class="nav-icon-container">
                        <div class="nav-icon-bg"></div>
                        <i class="nav-icon fas fa-user-alt"></i>
                    </div>
                    <span class="nav-label">Login</span>
                </a>
            @endif
        </div>
    </div>
</div>

<script>
    // iPhone-style touch feedback
    document.addEventListener('DOMContentLoaded', function() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            // Touch start effect
            item.addEventListener('touchstart', function(e) {
                this.style.transform = 'scale(0.95)';
            }, { passive: true });
            
            // Touch end effect
            item.addEventListener('touchend', function(e) {
                this.style.transform = 'scale(1)';
                
                // Update active state for regular links (not JavaScript ones)
                if (!this.hasAttribute('onclick')) {
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                }
            }, { passive: true });
            
            // Touch cancel effect
            item.addEventListener('touchcancel', function(e) {
                this.style.transform = 'scale(1)';
            }, { passive: true });
        });
        
        // Set initial active state based on current URL
        const currentUrl = window.location.href;
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href !== 'javascript:void(0);' && currentUrl.includes(href)) {
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            }
        });
    });
    
    // Add subtle parallax effect on scroll (iPhone-like)
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.bottom-navbar');
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop) {
            // Scrolling down - hide navbar slightly
            navbar.style.transform = 'translateY(10px)';
            navbar.style.opacity = '0.9';
        } else {
            // Scrolling up - show navbar
            navbar.style.transform = 'translateY(0)';
            navbar.style.opacity = '1';
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
</script>
    @if ($status)
        <input type="text" class="form-control" name="productrq_status" id="productrq_status"
            value="{{ $status }}" hidden>
    @else
        <input type="text" class="form-control" name="productrq_status" id="productrq_status" value="all" hidden>
    @endif
</div>
</div>
</div>


@section('subscript')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-datatables-checkboxes@1.2.13/js/dataTables.checkboxes.min.js"></script>
@endsection
<script>
    function setpaymenttype(id,text){
        $('.copays').css('border','2px solid');
        $('#copay'+id).css('border','3px solid red');
        $('#account').text(text);
    }

    $(document).ready(function() {
        var statusproductrq = $('#productrq_status').val();

        var productrqinfotbl = $('#productrqinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('withdrew/data/') }}" + '/' + statusproductrq,
            },
            columnDefs: [{
                targets: 0,
                checkboxes: {
                    selectRow: false,
                },
            }, ],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'user'
                },
                {
                    data: 'paymenttype_name'
                },
                {
                    data: 'to_account_number'
                },
                {
                    data: 'withdrew_amount',
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },

            ]
        });


        //edit menu
        $(document).on('click', '#editFrdBtn', function() {
            var id = $(this).attr('data-id');

            $.ajax({
                type: 'GET',
                url: "{{ url('withdrew') }}/" + id + "/edit",

                success: function(data) {
                    $('#EditMenu').find('#to_account_number').val(data.to_account_number);
                    $('#EditMenu').find('#account').text(data.paymenttype_name);
                    $('#EditMenu').find('#withdrew_amount').val(data.withdrew_amount);
                    $('#copay'+data.paymenttype_id).css('border','3px solid red');
                    $('#EditMenu').find('#withdrew_id').val(data.id);

                    $('#EditMenu').find('#status').val(data.status);
                    $('#EditMenu').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update menu
        $('#EditMenu').submit(function(e) {
            e.preventDefault();
            let menuId = $('#withdrew_id').val();

            $.ajax({
                type: 'POST',
                url: 'update/' + menuId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditMenu').find('#to_account_number').val('');
                    $('#EditMenu').find('#account').text('');
                    $('#EditMenu').find('#withdrew_amount').val('');
                    $('.copays').css('border','2px solid');
                    $('#EditMenu').find('#withdrew_id').val('');
                    $('#EditMenu').find('#status').val('');

                    swal({
                        title: "Withdrew request update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    productrqinfotbl.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });



    });

</script>

@endsection
