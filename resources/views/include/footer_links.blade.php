

<div class="scrollToTop">
    <span class="arrow"><i class="ri-arrow-up-circle-fill fs-20"></i></span>
</div>
<div id="responsive-overlay"></div>
<script src="{{ $actual_url.'/admin_assets/libs/jquery/jquery.min.js'}}"></script>

<script src="{{$actual_url.'/admin_assets/libs/@popperjs/core/umd/popper.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/bootstrap/js/bootstrap.bundle.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/js/defaultmenu.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/node-waves/waves.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/js/sticky.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/simplebar/simplebar.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/js/simplebar.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/@simonwep/pickr/pickr.es5.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/jsvectormap/js/jsvectormap.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/jsvectormap/maps/world-merc.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/libs/chart.js/chart.min.js'}}"></script>
{{-- <script src="{{$actual_url.'/admin_assets/js/sales-dashboard.js'}}"></script> --}}
<script src="{{$actual_url.'/admin_assets/js/custom-switcher.min.js'}}"></script>
<script src="{{$actual_url.'/admin_assets/js/custom.js'}}"></script>

<script src="{{ $actual_url.'/admin_assets/libs/izitoast/js/iziToast.min.js'}}"></script>
<script src="{{ $actual_url.'/admin_assets/js/validation.js'}}"></script>
<script src="{{ $actual_url.'/admin_assets/libs/select2/select2.min.js'}}"></script>
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>



{{-- <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script> --}}
{{-- if required --}}
{{-- <script src="{{ $actual_url.'/admin_assets/libs/flatpickr/flatpickr.min.js'}}"></script> --}}
{{-- <script src="{{ $actual_url.'/admin_assets/js/date&time_pickers.js'}}"></script> --}}







<script>
    function openCompletedModal(event) {
        event.preventDefault();

        const completedModal = new bootstrap.Modal(document.getElementById('completedModal'), {
            backdrop: false // prevents the background from going dark again
        });

        completedModal.show();
    }

</script>

