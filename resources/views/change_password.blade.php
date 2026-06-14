<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient" data-menu-styles="light">
<head>
    @include('include/meta_tags')
    @include('include/header_links')
    @include('include/datatable_css_link')
</head>
<body>
    @include('include/switcher')
    @include('include/loader')
    <div class="page">
        @include('include/top')
        @include('include/left')
        @include('include/top_effect')
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xxl-12 col-xl-12">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="col card-background-mt flex-fill">
                                    <div class="card custom-card">
                                        <div class="card-body">

                                            <form id="changepass" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <input type="hidden" id="login_type" name="login_type" value="{{ Session::get('login_type') }}">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="name" class="form-label mb-0">Old Password <span class="text-danger">*</span></label>
                                                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="text" class="form-label mb-0">New Password <span class="text-danger">*</span></label>
                                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="text" class="form-label mb-0">Confirm Password <span class="text-danger">*</span></label>
                                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                        </div>
                                                    </div>
                                                </div>
                                             
                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                    <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                                                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script>
        $('#changepass').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                $('#submitSpinner').removeClass('d-none');
                $('#submitText').text('Processing...');
                $('#submitBtn').prop('disabled', true);
                $.ajax({
                    url: "{{ route('UpdatePass') }}",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#submitSpinner').addClass('d-none');
                        $('#submitText').text('Submit');
                        $('#submitBtn').prop('disabled', false);
                        iziToast.success({
                            title: 'Success',
                            message: 'Password Update successfully!',
                            position: 'topRight',
                            transitionIn: 'bounceInLeft',
                            transitionOut: 'fadeOutRight',
                            timeout: 3000,
                            onClosed: function() {
                               window.location.href = "{{ route('login') }}";
                            }
                        });
                        

                    },
                    error: function(xhr) {
                         $('#submitSpinner').addClass('d-none');
                        $('#submitText').text('Submit');
                        $('#submitBtn').prop('disabled', false);

                        if (xhr.status === 422) {
                            let response = xhr.responseJSON;

                            $('#' + response.field).addClass('is-invalid');
                            $('#' + response.field).next('.invalid-feedback').text(response.message);

                            iziToast.warning({
                                title: 'Validation Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: xhr.responseJSON.message || 'Something went wrong',
                                position: 'topRight'
                            });
                        }
                    }

                });
            });

    </script>
</body>
</html>

