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

                                            <form id="qtyset" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <input type="hidden" value="{{ $data->qty_id }}" name="qty_id">
                                                <div class="row">
                                                    <!-- Required Fields -->
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label for="name" class="form-label mb-0">Qty Reminder set <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="qty_set" name="qty_set" value="{{ $data->qty_set }}" >
                                                            
                                                        </div>
                                                    </div>
                                                     <div class="col-md-6">
                                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                                                  
                                                </div>
                                                </div>
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
  
        $(document).ready(function() {
         

            $('#qtyset').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                // Reset validation
                $('.is-invalid').removeClass('is-invalid');
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                $.ajax({
                    url: "{{ route('qtystore') }}"
                    , type: "POST"
                    , data: formData
                    , contentType: false
                    , processData: false
                    , success: function(response) {
                        $('#submitBtn').prop('disabled', false).html('Submit');
                        iziToast.success({
                            title: 'Success'
                            , message: 'Qty Set successfully!'
                            , position: 'topRight'
                            , transitionIn: 'bounceInLeft'
                            , transitionOut: 'fadeOutRight'
                            , timeout: 1000
                            , onClosed: function() {
                                location.reload();
                            }
                        });
                    }
                    , 
                     error: function(xhr) {
                     $('#submitBtn').prop('disabled', false).html('Submit');

                     if (xhr.status === 422) {
                         let response = xhr.responseJSON;

                         if (response.errors) {
                             let firstField = null;

                             $.each(response.errors, function(field, messages) {
                                 let $input = $('[name="' + field + '"]');
                                 $input.addClass('is-invalid');
                                 $input.next('.invalid-feedback').text(messages[0]);

                                 // Focus on the first invalid input only
                                 if (!firstField) {
                                     firstField = $input;
                                 }
                             });

                             if (firstField) {
                                 firstField.focus();
                                 iziToast.warning({
                                     title: 'Validation Error',
                                     message: firstField.next('.invalid-feedback').text(),
                                     position: 'topRight'
                                 });
                             }
                         } else if (response.field && response.message) {
                             // fallback if custom response
                             let $field = $('#' + response.field);
                             $field.addClass('is-invalid');
                             $field.next('.invalid-feedback').text(response.message);
                             $field.focus();

                             iziToast.warning({
                                 title: 'Validation Error',
                                 message: response.message,
                                 position: 'topRight'
                             });
                         }
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
            
        });
      
    </script>
</body>
</html>

