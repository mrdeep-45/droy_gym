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
                                            <ul class="nav nav-pills justify-content-left nav-style-2 mb-1" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#company-list" aria-selected="true">List</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#new-company" aria-selected="false">New Company</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane border-0 show active text-muted px-1" id="company-list" role="tabpanel">
                                                    <div class="row">
                                                        <table id="company-data" class="table table-bordered company-data" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width:1%;">#</th>
                                                                    <th style="width:10%">Logo</th>
                                                                    <th style="width:15%">Name</th>
                                                                    <th style="width:15%">Email</th>
                                                                    <th style="width:10%">Contact</th>
                                                                    <th style="width:10%">Type</th>
                                                                    <th class="text-center" style="width:10%">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody></tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="tab-pane text-muted border-0 px-1" id="new-company" role="tabpanel">
                                                    <form id="companyForm" enctype="multipart/form-data" novalidate>
                                                        @csrf
                                                        <input type="hidden" id="company_id" name="company_id" value="">
                                                        <div class="row">
                                                            <!-- Required Fields -->
                                                            <div class="col-md-6">
                                                                <div class="mb-1">
                                                                    <label for="name" class="form-label mb-0">Company Name <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="name" name="name" required>
                                                                    <div class="invalid-feedback">Please provide a company name.</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-1">
                                                                    <label for="email" class="form-label mb-0">Email <span class="text-danger">*</span></label>
                                                                    <input type="email" class="form-control" id="email" name="email" required>
                                                                    <div class="invalid-feedback">Please provide a valid email.</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <!-- Optional Fields -->
                                                            <div class="col-md-6">
                                                                <div class="mb-1">
                                                                    <label for="contact_no" class="form-label mb-0">Contact Number</label>
                                                                    <input type="text" class="form-control" id="contact_no" name="contact_no" maxlength="20">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-1">
                                                                    <label for="project_name" class="form-label mb-0">Project Name</label>
                                                                    <input type="text" class="form-control" id="project_name" name="project_name" maxlength="100">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Logo with Image Validation -->
                                                        <div class="row">
    <div class="col-md-3">
        <div class="mb-1">
            <label for="logo" class="form-label mb-0">Company Logo</label>
            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
            <div class="invalid-feedback">Please upload a valid image file (JPEG, PNG, JPG, GIF).</div>
            <small class="text-muted">Max size: 2MB | Supported formats: JPEG, PNG, JPG, GIF</small>
        </div>
    </div>
    <div class="col-md-2">
        <img src="" class="img-thumb" id="logoPreview" alt="Logo Preview" style="width: 100%; height: auto; display: none;">
    </div>

    <div class="col-md-3">
        <div class="mb-1">
            <label for="favicon" class="form-label mb-0">Favicon</label>
            <input type="file" name="favicon" id="favicon" class="form-control" accept="image/*">
            <div class="invalid-feedback">Please upload a valid favicon image file.</div>
            <small class="text-muted">Max size: 512KB | Supported formats: ICO, PNG, JPG, GIF</small>
        </div>
    </div>
    <div class="col-md-1">
        <img src="" class="img-thumb" id="faviconPreview" alt="Favicon Preview" style="width: 32px; height: 32px; display: none;">
    </div>
</div>


                                                        <div class="accordion mb-3" id="menuPermissionsAccordion">
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="headingMenus">
                                                                    <button class="accordion-button bg-primary text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMenus" aria-expanded="false" aria-controls="collapseMenus">
                                                                        Menu Permissions
                                                                    </button>
                                                                </h2>
                                                                <div id="collapseMenus" class="accordion-collapse collapse show" aria-labelledby="headingMenus" data-bs-parent="#menuPermissionsAccordion">
                                                                    <div class="accordion-body">
                                                                        <div class="row">
                                                                            @foreach($menus as $menu)
                                                                            <div class="col-md-12 mb-3">
                                                                                <div class="card mb-0">
                                                                                    <div class="card-header">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input menu-checkbox" type="checkbox" id="menu_{{ $menu->c_menu_id }}" name="menus[]" value="{{ $menu->c_menu_id }}" data-menu-id="{{ $menu->c_menu_id }}">
                                                                                            <label class="form-check-label fw-bold" for="menu_{{ $menu->c_menu_id }}">
                                                                                                <i class="{{ $menu->menu_icon }} me-2"></i>
                                                                                                {{ $menu->menu_title }}
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                    @if($menu->is_submenu)
                                                                                    <div class="card-body">
                                                                                        <div class="row">
                                                                                            @foreach($menu->submenus as $submenu)
                                                                                            <div class="col-md-4 mb-2">
                                                                                                <div class="form-check">
                                                                                                    <input class="form-check-input submenu-checkbox " type="checkbox" id="submenu_{{ $submenu->c_sub_menu_id }}" name="submenus[]" value="{{ $submenu->c_sub_menu_id }}" data-menu-id="{{ $menu->c_menu_id }}">
                                                                                                    <label class="form-check-label" for="submenu_{{ $submenu->c_sub_menu_id }}">
                                                                                                        <i class="{{ $submenu->sub_menu_icon }} me-2"></i>
                                                                                                        {{ $submenu->sub_menu_title }}
                                                                                                    </label>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </div>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Additional Fields Section -->
                                                        <div class="accordion mb-3" id="companyDetailsAccordion">
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="headingDetails">
                                                                    <button class="accordion-button collapsed bg-primary text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetails" aria-expanded="false" aria-controls="collapseDetails">
                                                                        Additional Company Details
                                                                    </button>
                                                                </h2>
                                                                <div id="collapseDetails" class="accordion-collapse collapse" aria-labelledby="headingDetails" data-bs-parent="#companyDetailsAccordion">
                                                                    <div class="accordion-body">
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="registration_number" class="form-label mb-0">Registration Number</label>
                                                                                    <input type="text" class="form-control" id="registration_number" name="registration_number" maxlength="50">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="company_type" class="form-label mb-0">Company Type</label>
                                                                                    <input type="text" class="form-control" id="company_type" name="company_type" maxlength="50">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="industry" class="form-label mb-0">Industry</label>
                                                                                    <input type="text" class="form-control" id="industry" name="industry" maxlength="50">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="founded_date" class="form-label mb-0">Founded Date</label>
                                                                                    <input type="date" class="form-control" id="founded_date" name="founded_date">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <div class="mb-1">
                                                                                    <label for="address" class="form-label mb-0">Address</label>
                                                                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-md-4">
                                                                                <div class="mb-1">
                                                                                    <label for="country" class="form-label mb-0">Country</label>
                                                                                    <select class="form-control select2" id="country" name="country"></select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <div class="mb-1">
                                                                                    <label for="state" class="form-label mb-0">State</label>
                                                                                    <select class="form-control select2" id="state" name="state" disabled></select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <div class="mb-1">
                                                                                    <label for="city" class="form-label mb-0">City</label>
                                                                                    <select class="form-control select2" id="city" name="city" disabled></select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="postal_code" class="form-label mb-0">Postal Code</label>
                                                                                    <input type="text" class="form-control" id="postal_code" name="postal_code" maxlength="20">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="website" class="form-label mb-0">Website</label>
                                                                                    <input type="url" class="form-control" id="website" name="website" maxlength="100">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="mb-1">
                                                                                    <label for="tax_id" class="form-label mb-0">Tax ID</label>
                                                                                    <input type="text" class="form-control" id="tax_id" name="tax_id" maxlength="50">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <div class="mb-1">
                                                                                    <label for="description" class="form-label mb-0">Description</label>
                                                                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
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
            </div>
        </div>
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script>
        const img_url = "{{ $img_path }}";
const CompanyData = [];
        $(document).ready(function() {
            function handleLocationDropdowns(response) {
                // Initialize country dropdown
                var countrySelect = $('#country');

                // Clear any existing options
                countrySelect.empty();

                // If we have a country ID, fetch and set it
                if (response.country) {
                    $.ajax({
                        url: "{{ route('get.country') }}"
                        , data: {
                            fetch_one: true
                            , search: response.country
                        }
                        , dataType: 'json'
                        , success: function(data) {
                            if (data) {
                                var newOption = new Option(data.text, data.id, true, true);
                                countrySelect.append(newOption).trigger('change');

                                // Initialize state dropdown after country is set
                                initializeStateDropdown(response.country, response.state, response.city);
                            }
                        }
                    });
                }
            }

            function initializeStateDropdown(countryId, stateId, cityId) {
                var stateSelect = $('#state');

                // Clear any existing options
                stateSelect.empty().prop('disabled', !countryId);

                if (!countryId) return;

                // Initialize state dropdown with country ID
                stateSelect.select2({
                    ajax: {
                        url: "{{ route('get.state') }}"
                        , dataType: 'json'
                        , delay: 250
                        , data: function(params) {
                            return {
                                search: params.term
                                , page: params.page || 1
                                , country_id: countryId
                            };
                        }
                        , processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results
                                , pagination: {
                                    more: (params.page * 10) < data.total_count
                                }
                            };
                        }
                    }
                    , placeholder: 'Select State'
                    , minimumInputLength: 0
                });

                // If we have a state ID, fetch and set it
                if (stateId) {
                    $.ajax({
                        url: "{{ route('get.state') }}"
                        , data: {
                            fetch_one: true
                            , search: stateId
                            , country_id: countryId
                        }
                        , dataType: 'json'
                        , success: function(data) {
                            if (data) {
                                var newOption = new Option(data.text, data.id, true, true);
                                stateSelect.append(newOption).trigger('change');

                                // Initialize city dropdown after state is set
                                initializeCityDropdown(stateId, cityId);
                            }
                        }
                    });
                }
            }

            function initializeCityDropdown(stateId, cityId) {
                var citySelect = $('#city');

                // Clear any existing options
                citySelect.empty().prop('disabled', !stateId);

                if (!stateId) return;

                // Initialize city dropdown with state ID
                citySelect.select2({
                    ajax: {
                        url: "{{ route('get.city') }}"
                        , dataType: 'json'
                        , delay: 250
                        , data: function(params) {
                            return {
                                search: params.term
                                , page: params.page || 1
                                , state_id: stateId
                            };
                        }
                        , processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results
                                , pagination: {
                                    more: (params.page * 10) < data.total_count
                                }
                            };
                        }
                    }
                    , placeholder: 'Select City'
                    , minimumInputLength: 0
                });

                // If we have a city ID, fetch and set it
                if (cityId) {
                    $.ajax({
                        url: "{{ route('get.city') }}"
                        , data: {
                            fetch_one: true
                            , search: cityId
                            , state_id: stateId
                        }
                        , dataType: 'json'
                        , success: function(data) {
                            if (data) {
                                var newOption = new Option(data.text, data.id, true, true);
                                citySelect.append(newOption).trigger('change');
                            }
                        }
                    });
                }
            }

            $(document).on('click', '.edit-btn', function() {
                var companyId = $(this).data('id');

                // Show the new-company tab
                $('[href="#new-company"]').tab('show');

                // Fetch company data
                $.ajax({
                    url: "{{ route('companies.edit', ':id') }}".replace(':id', companyId)
                    , type: 'GET'
                    , success: function(response) {
                        // Fill form with company data
                        $('#company_id').val(response.company_id);
                        $('#name').val(response.name);
                        $('#email').val(response.email);
                        $('#contact_no').val(response.contact_no);
                        $('#project_name').val(response.project_name);
                        $('#registration_number').val(response.registration_number);
                        $('#company_type').val(response.company_type);
                        $('#industry').val(response.industry);
                        $('#founded_date').val(response.founded_date);
                        $('#address').val(response.address);
                        $('#city').val(response.city);
                        $('#state').val(response.state);
                        $('#country').val(response.country);
                        $('#postal_code').val(response.postal_code);
                        $('#website').val(response.website);
                        $('#tax_id').val(response.tax_id);
                        $('#description').val(response.description);
                        handleLocationDropdowns(response);
                        // Set logo preview if exists
                        if (response.logo) {
                            var logoUrl = img_url + "/company/" + response.logo;
                            $('#logoPreview').attr('src', logoUrl).show();
                        } else {
                            $('#logoPreview').hide();
                        }

                        if (response.favicon) {
                            var faviconUrl = img_url + "/company/" + response.favicon;
                            $('#faviconPreview').attr('src', faviconUrl).show();
                        } else {
                            $('#faviconPreview').hide();
                        }

                        // Check menu permissions
                        $('.menu-checkbox, .submenu-checkbox').prop('checked', false);

                        if (response.menus && response.menus.length > 0) {
                            response.menus.forEach(function(menuId) {
                                $('#menu_' + menuId).prop('checked', true);
                            });
                        }

                        if (response.submenus && response.submenus.length > 0) {
                            response.submenus.forEach(function(submenuId) {
                                $('#submenu_' + submenuId).prop('checked', true);
                            });
                        }

                        // Change submit button text
                        $('#submitBtn').text('Update');
                    }
                    , error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $('#companyForm').on('reset', function() {
    $('#company_id').val('');
    $('#logoPreview').hide().attr('src', '');
    $('#faviconPreview').hide().attr('src', '');
    $('.menu-checkbox, .submenu-checkbox').prop('checked', false);
    $('#submitBtn').text('Submit');

    // Reset location dropdowns
    $('#country').val(null).trigger('change');
    $('#state').val(null).trigger('change').prop('disabled', true);
    $('#city').val(null).trigger('change').prop('disabled', true);

    // If you're using Select2, you might also need to reset the selections visually
    if ($('#country').data('select2')) {
        $('#country').val(null).trigger('change.select2');
    }
    if ($('#state').data('select2')) {
        $('#state').val(null).trigger('change.select2');
    }
    if ($('#city').data('select2')) {
        $('#city').val(null).trigger('change.select2');
    }
});
            $('.nav-link[href="#company-list"]').on('click', function() {
                $('.nav-link[href="#new-company"]').text('New Company');
                $('#companyForm')[0].reset();
                $('.menu-checkbox, .submenu-checkbox, .permission-checkbox, .all-checkbox').prop('checked', false);
                $('#RoleId').val(null).trigger('change');
                if ($('#logoPreview').length) {
                    $('#logoPreview').hide();
                }if ($('#faviconPreview').length) {
                    $('#faviconPreview').hide();
                }
            });

            $('#companyForm').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                // Reset validation
                $('.is-invalid').removeClass('is-invalid');
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                $.ajax({
                    url: "{{ route('companies/store') }}"
                    , type: "POST"
                    , data: formData
                    , contentType: false
                    , processData: false
                    , success: function(response) {
                        $('#submitBtn').prop('disabled', false).html('Submit');
                        iziToast.success({
                            title: 'Success'
                            , message: 'Company created successfully!'
                            , position: 'topRight'
                            , transitionIn: 'bounceInLeft'
                            , transitionOut: 'fadeOutRight'
                            , timeout: 1000
                            , onClosed: function() {
                                CompanyData.ajax.reload(null, false);
                                $('a[href="#company-list"]').tab('show');
                                $('#companyForm')[0].reset();
                                if ($('#logoPreview').length) {
                                    $('#logoPreview').hide();
                                }
                                if ($('#faviconPreview').length) {
                                    $('#faviconPreview').hide();
                                }
                                $('#country').val(null).trigger('change');
                                $('#state').val(null).trigger('change').prop('disabled', true);
                                $('#city').val(null).trigger('change').prop('disabled', true);

                                if ($('#country').data('select2')) {
                                    $('#country').val(null).trigger('change.select2');
                                }
                                if ($('#state').data('select2')) {
                                    $('#state').val(null).trigger('change.select2');
                                }
                                if ($('#city').data('select2')) {
                                    $('#city').val(null).trigger('change.select2');
                                }
                            }
                        });
                        $('#companyForm')[0].reset();
                        if ($('#logoPreview').length) {
                            $('#logoPreview').hide();
                        }
                        if ($('#faviconPreview').length) {
                            $('#faviconPreview').hide();
                        }
                    }
                    , error: function(xhr) {
                        $('#submitBtn').prop('disabled', false).html('Submit');
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#' + key).addClass('is-invalid');
                                $('#' + key).next('.invalid-feedback').text(value[0]);
                            });
                            // Show validation error notification
                            iziToast.warning({
                                title: 'Secure'
                                , message: 'Please fix the form errors'
                                , position: 'topRight'
                            });
                        } else {
                            // Show general error notification
                            iziToast.error({
                                title: 'Error'
                                , message: 'An error occurred. Please try again.'
                                , position: 'topRight'
                            });
                        }
                    }
                });
            });
            // Image validation
            $('#logo').change(function() {
                const file = this.files[0];
                if (file) {
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    const maxSize = 2048; // 2MB in KB
                    if (!validTypes.includes(file.type)) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').text('Invalid file type. Only JPEG, PNG, JPG, GIF are allowed.');
                        $(this).val('');
                        // Show error notification
                        iziToast.warning({
                            title: 'Secure'
                            , message: 'Invalid file type. Only images are allowed.'
                            , position: 'topRight'
                        });
                    } else if (file.size > maxSize * 1024) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').text('File size exceeds 2MB limit.');
                        $(this).val('');
                        // Show error notification
                        iziToast.warning({
                            title: 'Secure'
                            , message: 'File size exceeds 2MB limit.'
                            , position: 'topRight'
                        });
                    } else {
                        $(this).removeClass('is-invalid');
                        // Optional: Show preview
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            $('#logoPreview').attr('src', e.target.result).show();
                        }
                        reader.readAsDataURL(file);
                    }
                }
            });
            $('#favicon').change(function() {
                const file = this.files[0];
                if (file) {
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    const maxSize = 2048; // 2MB in KB
                    if (!validTypes.includes(file.type)) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').text('Invalid file type. Only JPEG, PNG, JPG, GIF are allowed.');
                        $(this).val('');
                        // Show error notification
                        iziToast.warning({
                            title: 'Secure'
                            , message: 'Invalid file type. Only images are allowed.'
                            , position: 'topRight'
                        });
                    } else if (file.size > maxSize * 1024) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').text('File size exceeds 2MB limit.');
                        $(this).val('');
                        // Show error notification
                        iziToast.warning({
                            title: 'Secure'
                            , message: 'File size exceeds 2MB limit.'
                            , position: 'topRight'
                        });
                    } else {
                        $(this).removeClass('is-invalid');
                        // Optional: Show preview
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            $('#faviconPreview').attr('src', e.target.result).show();
                        }
                        reader.readAsDataURL(file);
                    }
                }
            });
            var CompanyData = $('#company-data').DataTable({
                processing: false
                , serverSide: true
                , ajax: "{{ route('companies/list') }}"
                , columns: [{
                        data: 'DT_RowIndex'
                        , name: 'DT_RowIndex'
                        , orderable: false
                        , searchable: false
                        , className: 'text-center'
                    }
                    , {
                        data: 'logo'
                        , name: 'logo'
                        , orderable: false
                        , searchable: false
                        , className: 'text-center'
                    }
                    , {
                        data: 'name'
                        , name: 'name'
                    }
                    , {
                        data: 'email'
                        , name: 'email'
                    }
                    , {
                        data: 'contact_no'
                        , name: 'contact_no'
                    }
                    , {
                        data: 'company_type'
                        , name: 'company_type'
                    }
                    , {
                        data: 'action'
                        , name: 'action'
                        , orderable: false
                        , searchable: false
                        , className: 'text-center'
                    }
                ]
                , language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>'
                    , emptyTable: 'No companies found'
                    , info: 'Showing _START_ to _END_ of _TOTAL_ companies'
                    , infoEmpty: 'Showing 0 to 0 of 0 companies'
                    , infoFiltered: '(filtered from _MAX_ total companies)'
                }
            });

            // Delete button functionality
            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to delete this company?')) {
                    var companyId = $(this).data('id');
                    console.log('Delete company with ID:', companyId);
                }
            });

        });
        $(document).ready(function() {
            // Initialize Select2 for all dropdowns
            $('.select2').select2({
                ajax: {
                    delay: 250, // wait 250ms before triggering the request
                    cache: true
                }
            });

            // Country dropdown
            $('#country').select2({
                ajax: {
                    url: "{{ route('get.country') }}"
                    , dataType: 'json'
                    , delay: 250
                    , data: function(params) {
                        return {
                            search: params.term, // search term
                            page: params.page || 1
                        };
                    }
                    , processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results
                            , pagination: {
                                more: (params.page * 10) < data.total_count
                            }
                        };
                    }
                }
                , placeholder: 'Select Country'
                , minimumInputLength: 0
            });

            // When country is selected, load states
            $('#country').on('change', function() {
                var countryId = $(this).val();
                $('#state').val(null).trigger('change').prop('disabled', !countryId);
                $('#city').val(null).trigger('change').prop('disabled', true);

                if (countryId) {
                    // Initialize state dropdown with country ID
                    $('#state').select2({
                        ajax: {
                            url: "{{ route('get.state') }}"
                            , dataType: 'json'
                            , delay: 250
                            , data: function(params) {
                                return {
                                    search: params.term
                                    , page: params.page || 1
                                    , country_id: countryId
                                };
                            }
                            , processResults: function(data, params) {
                                params.page = params.page || 1;
                                return {
                                    results: data.results
                                    , pagination: {
                                        more: (params.page * 10) < data.total_count
                                    }
                                };
                            }
                        }
                        , placeholder: 'Select State'
                        , minimumInputLength: 0
                    });
                }
            });

            // When state is selected, load cities
            $('#state').on('change', function() {
                var stateId = $(this).val();
                $('#city').val(null).trigger('change').prop('disabled', !stateId);

                if (stateId) {
                    // Initialize city dropdown with state ID
                    $('#city').select2({
                        ajax: {
                            url: "{{ route('get.city') }}"
                            , dataType: 'json'
                            , delay: 250
                            , data: function(params) {
                                return {
                                    search: params.term
                                    , page: params.page || 1
                                    , state_id: stateId
                                };
                            }
                            , processResults: function(data, params) {
                                params.page = params.page || 1;
                                return {
                                    results: data.results
                                    , pagination: {
                                        more: (params.page * 10) < data.total_count
                                    }
                                };
                            }
                        }
                        , placeholder: 'Select City'
                        , minimumInputLength: 0
                    });
                }
            });
        });

    </script>
</body>
</html>

