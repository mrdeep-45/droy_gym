<!-- Favicon -->

@php
$company_data = get_company_details(getCreatedBy());
$userLogo = $company_data && $company_data->favicon ? $company_data->favicon : null;
$fav_Alt = $company_data && $company_data->name ? $company_data->name : null;
$fav_logoUrl = $userLogo ? $actual_url.'/uploads/company/' . $userLogo : $actual_url . '/admin_assets/images/brand-logos/fav.ico';
@endphp

<link rel="icon" href="{{$fav_logoUrl}}" type="image/x-icon">

<!-- Choices JS -->
<script src="{{$actual_url.'/admin_assets/libs/choices.js/public/assets/scripts/choices.min.js'}}"></script>

<!-- Main Theme Js -->
<script src="{{$actual_url.'/admin_assets/js/main.js'}}"></script>

<!-- Bootstrap Css -->
<link id="style" href="{{$actual_url.'/admin_assets/libs/bootstrap/css/bootstrap.min.css'}}" rel="stylesheet">

<!-- Style Css -->
<link href="{{$actual_url.'/admin_assets/css/styles.css'}}" rel="stylesheet">

<!-- Icons Css -->
<link href="{{$actual_url.'/admin_assets/css/icons.css'}}" rel="stylesheet">

<!-- Node Waves Css -->
<link href="{{$actual_url.'/admin_assets/libs/node-waves/waves.min.css'}}" rel="stylesheet">

<!-- Simplebar Css -->
<link href="{{$actual_url.'/admin_assets/libs/simplebar/simplebar.min.css'}}" rel="stylesheet">

<!-- Color Picker Css -->
<link rel="stylesheet" href="{{$actual_url.'/admin_assets/libs/flatpickr/flatpickr.min.css'}}">
<link rel="stylesheet" href="{{$actual_url.'/admin_assets/libs/@simonwep/pickr/themes/nano.min.css'}}">

<!-- Choices Css -->
<link rel="stylesheet" href="{{$actual_url.'/admin_assets/libs/choices.js/public/assets/styles/choices.min.css'}}">

<link rel="stylesheet" href="{{$actual_url.'/admin_assets/libs/jsvectormap/css/jsvectormap.min.css'}}">

<link href="{{ $actual_url.'/admin_assets/libs/izitoast/css/iziToast.min.css' }}" rel="stylesheet">
<link rel="stylesheet" href="{{ $actual_url.'/admin_assets/libs/select2/select2.min.css'}}">



{{-- <script defer>
        document.addEventListener('DOMContentLoaded', function () {
            const permissions = @json(checkPermissions(Request::route()->getName()));
            const roles = ['canView', 'canCreate', 'canUpdate', 'canDelete'];

            document.body.style.display = 'none';

            roles.forEach(role => {
                if (!permissions[role]) {
                    document.querySelectorAll(`.${role}`).forEach(el => el.remove());
                }
            });

            document.body.style.display = 'block';
        });
    </script> --}}

