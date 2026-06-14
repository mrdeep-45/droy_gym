<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient"
    data-menu-styles="light">

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

                                            <form id="mailConfigForm" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <input type="hidden" id="company_id" name="company_id"
                                                    value="{{ Session::get('company_id') }}">
                                                <div class="row">
                                                    <!-- Mailer -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_MAILER"
                                                                class="form-label mb-0">Mailer</label>
                                                            <div class="d-flex">
                                                                <input type="text" class="form-control" id="MAIL_MAILER"
                                                                    name="MAIL_MAILER"
                                                                    value="{{ $data->MAIL_MAILER ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <!-- Host -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_HOST" class="form-label mb-0">Host</label>
                                                            <div class="d-flex">
                                                                <input type="text" class="form-control" id="MAIL_HOST"
                                                                    name="MAIL_HOST"
                                                                    value="{{ $data->MAIL_HOST ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Port -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_PORT" class="form-label mb-0">Port</label>
                                                            <div class="d-flex">
                                                                <input type="number" class="form-control" id="MAIL_PORT"
                                                                    name="MAIL_PORT"
                                                                    value="{{ $data->MAIL_PORT ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Username -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_USERNAME"
                                                                class="form-label mb-0">Username</label>
                                                            <div class="d-flex">
                                                                <input type="text" class="form-control"
                                                                    id="MAIL_USERNAME" name="MAIL_USERNAME"
                                                                    value="{{ $data->MAIL_USERNAME ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Password -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_PASSWORD"
                                                                class="form-label mb-0">Password</label>
                                                            <div class="d-flex">
                                                                <input type="password" class="form-control"
                                                                    id="MAIL_PASSWORD" name="MAIL_PASSWORD"
                                                                    value="{{ $data->MAIL_PASSWORD ?? '' }}">

                                                                <button type="button"
                                                                    class="btn btn-outline-secondary ms-1"
                                                                    id="togglePassword">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>

                                                                <button class="bg-green border-0 model-submit ms-1">
                                                                    <i class="bi bi-check2"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Encryption -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_ENCRYPTION"
                                                                class="form-label mb-0">Encryption</label>
                                                            <div class="d-flex">
                                                                <input type="text" class="form-control"
                                                                    id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION"
                                                                    value="{{ $data->MAIL_ENCRYPTION ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- From Address -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_FROM_ADDRESS" class="form-label mb-0">From
                                                                Address</label>
                                                            <div class="d-flex">
                                                                <input type="email" class="form-control"
                                                                    id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS"
                                                                    value="{{ $data->MAIL_FROM_ADDRESS ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- From Name -->
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <label for="MAIL_FROM_NAME" class="form-label mb-0">From
                                                                Name</label>
                                                            <div class="d-flex">
                                                                <input type="text" class="form-control"
                                                                    id="MAIL_FROM_NAME" name="MAIL_FROM_NAME"
                                                                    value="{{ $data->MAIL_FROM_NAME ?? '' }}">
                                                                <button class="bg-green border-0 model-submit"> <i
                                                                        class="bi bi-check2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                                    <button type="reset"
                                                        class="btn btn-secondary me-md-2">Reset</button>
                                                    <button type="button" class="btn btn-success"
                                                        id="doneBtn">Done</button>
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
        $(document).ready(function () {
            $(document).on("click", "#togglePassword", function () {
                const passwordInput = $("#MAIL_PASSWORD");
                const icon = $(this).find("i");

                const type = passwordInput.attr("type") === "password" ? "text" : "password";
                passwordInput.attr("type", type);

                if (type === "password") {
                    icon.removeClass("bi-eye-slash").addClass("bi-eye");
                } else {
                    icon.removeClass("bi-eye").addClass("bi-eye-slash");
                }
            });

            $(document).on("click", ".model-submit", function (e) {
                e.preventDefault();

                let $btn = $(this);
                let $wrapper = $(this).closest(".d-flex");
                let $input = $wrapper.find("input, select");
                let inputVal = $input.val();
                let fieldName = $input.attr("name");


                console.log({
                    field: fieldName,
                    value: inputVal,

                });

                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                    }
                });

                $.ajax({
                    url: "{{ route('mail.config.update') }}",
                    type: "POST",
                    data: {
                        [fieldName]: inputVal,

                    },
                    success: function (response) {
                        $input.prop("disabled", true);
                        if (response.success) {
                            iziToast.success({
                                title: "Success",
                                message: response.message,
                                position: "topRight",
                                transitionIn: "bounceInLeft",
                                transitionOut: "fadeOutRight",
                                timeout: 1000
                            });
                            table.ajax.reload();
                            $btn.remove();
                        } else {
                            iziToast.warning({
                                title: "Warning",
                                message: response.message,
                                position: "topRight",
                                transitionIn: "bounceInLeft"
                            });
                        }
                    },
                    error: function (xhr) {
                        iziToast.error({
                            title: "Error",
                            message: xhr.responseJSON?.message || "Something went wrong!",
                            position: "topRight",
                            transitionOut: "fadeOutRight",
                            timeout: 5000
                        });
                    }
                });
            });

//             $("#doneBtn").on("click", function (e) {
//     e.preventDefault();

//     let formData = {};
//     $("#mailConfigForm")
//         .find("input, select")
//         .each(function () {
//             const name = $(this).attr("name");
//             const value = $(this).val();
//             formData[name] = value;
//         });

//     $.ajaxSetup({
//         headers: {
//             "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
//         }
//     });

//     $.ajax({
//         url: "{{ route('mail.config.update') }}",
//         type: "POST",
//         data: formData,
//         success: function (response) {
//             if (response.success) {
//                 iziToast.success({
//                     title: "Success",
//                     message: response.message,
//                     position: "topRight",
//                     timeout: 2000
//                 });
//             } else {
//                 iziToast.warning({
//                     title: "Warning",
//                     message: response.message,
//                     position: "topRight"
//                 });
//             }
//         },
//         error: function (xhr) {
//             iziToast.error({
//                 title: "Error",
//                 message: xhr.responseJSON?.message || "Something went wrong!",
//                 position: "topRight",
//                 timeout: 5000
//             });
//         }
//     });
// });


        })
    </script>

</body>

</html>