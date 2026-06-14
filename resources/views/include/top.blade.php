@php
$company_data = get_company_details(getCreatedBy());
$userLogo = $company_data && $company_data->logo ? $company_data->logo : null;
$Alt = $company_data && $company_data->name ? $company_data->name : null;
$logoUrl = $userLogo ? $actual_url.'/uploads/company/' . $userLogo : $actual_url . '/admin_assets/images/brand-logos/enli-logo.png';
$loginType = session('login_type');
$dashboardUrl = ($loginType == 'Company' || $loginType == 'Staff') ? route('company/dashboard') : route('dashboard');

$company_data_fav = get_company_details(getCreatedBy());
$userLogo_fav = $company_data_fav && $company_data_fav->favicon ? $company_data_fav->favicon : null;
$fav_Alt = $company_data_fav && $company_data_fav->name ? $company_data_fav->name : null;
$fav_logoUrl = $userLogo_fav ? $actual_url.'/uploads/company/' . $userLogo_fav : $actual_url . '/admin_assets/images/brand-logos/fav.ico';
@endphp

<style>

</style>
<header class="app-header">

    <div class="main-header-container container-fluid">

        <div class="header-content-left">

            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="{{ route('company/dashboard') }}" class="header-logo">
                        <img src="{{ $fav_logoUrl }}" alt="{{ $fav_Alt }}" class="desktop-logo">
                        <img src="{{ $fav_logoUrl }}" alt="{{ $fav_Alt }}" class="toggle-logo">
                        <img src="{{ $fav_logoUrl }}" alt="{{ $fav_Alt }}" class="desktop-dark">
                        <img src="{{ $fav_logoUrl }}" alt="{{ $fav_Alt }}" class="toggle-dark">
                    </a>
                </div>
            </div>

            <div class="header-element">
                <a aria-label="anchor" href="javascript:void(0);" class="sidemenu-toggle header-link" data-bs-toggle="sidebar">
                    <span class="open-toggle me-2">
                        <i class="bx bx-menu header-link-icon"></i>
                    </span>
                </a>
                    <div class="main-header-center d-none d-lg-block header-link">
                        <input type="text" class="form-control form-control-lg" id="typehead" placeholder="Search for results... (Ctrl + Shift + F)" autocomplete="off">
                        <button type="button" aria-label="button" class="btn pe-1"><i class="fe fe-search" aria-hidden="true"></i></button>
                        <div id="headersearch" class="header-search">
                            <div class="p-3">
                                <div class="mt-3">
                                    <div id="search-results">
                                        <p class="fw-semibold text-center text-muted mb-2 fs-13">Quick Menu Results</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                {{-- <div class="main-header-center  d-none d-lg-block  header-link">
                    <input type="text" class="form-control form-control-lg" id="typehead" placeholder="Search for results..." autocomplete="off">
                    <button type="button" aria-label="button" class="btn pe-1"><i class="fe fe-search" aria-hidden="true"></i></button>
                    <div id="headersearch" class="header-search">
                        <div class="p-3">
                            <div class="mt-3">
                                <p class="fw-semibold text-muted mb-2 fs-13">Apps and pages</p>
                                <ul class="ps-2">
                                    <li class="p-1 d-flex align-items-center text-muted mb-2 search-app">
                                        <a href="full-calendar.html"><span><i class="bx bx-calendar me-2 fs-14 bg-primary-transparent p-2 rounded-circle"></i>Calendar</span></a>
                                    </li>
                                    <li class="p-1 d-flex align-items-center text-muted mb-2 search-app">
                                        <a href="mail.html"><span><i class="bx bx-envelope me-2 fs-14 bg-primary-transparent p-2 rounded-circle"></i>Mail</span></a>
                                    </li>
                                    <li class="p-1 d-flex align-items-center text-muted mb-2 search-app">
                                        <a href="buttons.html"><span><i class="bx bx-dice-1 me-2 fs-14 bg-primary-transparent p-2 rounded-circle"></i>Buttons</span></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div> --}}

            </div>

            <div class="header-element header-search d-lg-none d-none ">
                <a aria-label="anchor" href="javascript:void(0);" class="header-link" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="bx bx-search-alt-2 header-link-icon"></i>
                </a>
            </div>

        </div>

        <div class="header-content-right">



            <div class="header-element header-theme-mode d-none">
                <a aria-label="anchor" href="javascript:void(0);" class="header-link layout-setting">
                    <i class="bx bx-sun bx-flip-horizontal header-link-icon ionicon  dark-layout"></i>
                    <i class="bx bx-moon bx-flip-horizontal header-link-icon ionicon light-layout"></i>
                </a>
            </div>
            <div class="header-element d-flex header-settings header-shortcuts-dropdown ">
                <a aria-label="anchor" href="javascript:void(0);" class=" header-link nav-link icon" data-bs-toggle="offcanvas" data-bs-target="#apps" aria-controls="apps">
                    <i class="bx bx-category  header-link-icon"></i>
                </a>
            </div>

            <div class="offcanvas offcanvas-end wd-330" tabindex="-1" id="apps" aria-labelledby="appsLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 id="appsLabel" class="mb-0 fs-18">Related Apps</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"> <i class="bx bx-x   apps-btn-close"></i></button>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="full-calendar.html">
                                <div class="text-center p-3 related-app border">
                                    <span class="avatar fs-23 bg-success-transparent p-2 mb-2">
                                        <i class="bx bx-calendar text-success"></i>
                                    </span>
                                    <span class="d-block fs-13 text-muted fw-semibold">Calendar</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="mail.html">
                                <div class="text-center p-3 related-app border">
                                    <span class="avatar  fs-23 bg-info-transparent p-2 mb-2">
                                        <i class="bx bx-envelope  text-info"></i>
                                    </span>
                                    <span class="d-block fs-13 text-muted fw-semibold">Mail</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="profile.html">
                                <div class="text-center p-3 related-app border">
                                    <span class="avatar bg-warning-transparent fs-23 bg p-2 mb-2">
                                        <i class="bx bx-user  text-warning"></i>
                                    </span>
                                    <span class="d-block fs-13 text-muted fw-semibold">Profile</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="chat.html">
                                <div class="text-center p-3 related-app border">
                                    <span class="avatar    bg-pink-transparent fs-23 bg p-2 mb-2">
                                        <i class="bx bx-chat text-pink"></i>
                                    </span>
                                    <span class="d-block fs-13 text-muted fw-semibold">Chat</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="contacts.html">
                                <div class="text-center p-3 related-app border">
                                    <span class="avatar    bg-secondary-transparent fs-23 bg p-2 mb-2">
                                        <i class="bx bx-phone text-secondary"></i>
                                    </span>
                                    <span class="d-block fs-13 text-muted fw-semibold">Contacts</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="mail-settings.html">
                                <div class="text-center p-3 related-app border">
                                    <span class="avatar    bg-teal-transparent fs-23 bg p-2 mb-2">
                                        <i class="bx bx-cog text-teal"></i>
                                    </span>
                                    <span class="d-block fs-13 text-muted fw-semibold">Settings</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-element header-fullscreen">
                <a aria-label="anchor" onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
                    <i class="bx bx-fullscreen header-link-icon  full-screen-open"></i>
                    <i class="bx bx-exit-fullscreen header-link-icon  full-screen-close  d-none"></i>
                </a>
            </div>
            <div class="header-element d-flex header-settings d-none">
                <a aria-label="anchor" href="javascript:void(0);" class=" header-link nav-link icon me-1" data-bs-toggle="offcanvas" data-bs-target="#sidebar-right" aria-controls="sidebar-right">
                    <i class="bx bx-slider header-link-icon"></i>
                </a>
            </div>
            <div class="header-element mainuserProfile">
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="d-sm-flex wd-100p">

                            <div class="avatar avatar-sm"><img alt="{{ $fav_Alt }}" class="rounded-circle" src="{{ $fav_logoUrl }}"></div>
                            <div class="ms-2 my-auto d-none d-xl-flex">
                                <h6 class=" font-weight-semibold mb-0 fs-13 user-name d-sm-block d-none">{{ get_session_name() }}</h6>
                            </div>
                        </div>
                    </div>
                </a>
                <ul class="dropdown-menu  border-0 main-header-dropdown  overflow-hidden header-profile-dropdown" aria-labelledby="mainHeaderProfile">
                     <li><a class="dropdown-item" href="{{ route('changepassword') }}"><i class="fs-13 me-2 bx bx-key"></i>Change Password</a></li> 
                    <li><a class="dropdown-item" href="{{ route('logout') }}"><i class="fs-13 me-2 bx bx-arrow-to-right"></i>Log Out</a></li>
                </ul>
            </div>
            <div class="header-element d-none">
                <a aria-label="anchor" href="javascript:void(0);" class="header-link switcher-icon ms-1" data-bs-toggle="offcanvas" data-bs-target="#switcher-canvas">
                    <i class="bx bx-cog bx-spin header-link-icon"></i>
                </a>
            </div>
        </div>
    </div>
</header>

