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
                                            <ul class="nav nav-pills justify-content-left nav-style-2 mb-1"
                                                role="tablist">
                                                @if (permissions_check('canView'))
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#plan-list"
                                                            aria-selected="true">List</a>
                                                    </li>
                                                @endif

                                                @if (permissions_check('canCreate'))
                                                    <li class="nav-item ">
                                                        <a class="nav-link" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#new-plan"
                                                            aria-selected="false" id="companyTabLabel">New Plan</a>
                                                    </li>
                                                @endif
                                            </ul>
                                            <div class="tab-content">
                                                @if (permissions_check('canView'))
                                                    <div class="tab-pane border-0 show active text-muted px-1 "
                                                        id="plan-list" role="tabpanel">
                                                        <div class="row">
                                                            <table id="planData"
                                                                class="table table-bordered  menu-submenu-data"
                                                                style="width:100%">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:1%;">#</th>

                                                                        <th style="width:20%;">Plan</th>
                                                                        <th style="width:10%;">Duration</th>
                                                                        <th style="width:10%;">Price</th>
                                                                        <th style="width:40%;">Description</th>

                                                                        <th class="text-center">Action </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (permissions_check('canCreate'))
                                                    <div class="tab-pane text-muted border-0 px-1 " id="new-plan"
                                                        role="tabpanel">
                                                        {{-- <div class="d-flex justify-content-end">
                                                        <small class="text-danger me-2">* Fields are mandatory</small>
                                                    </div> --}}
                                                       
                                                        <form id="plan_form" enctype="multipart/form-data"
                                                            novalidate>
                                                            @csrf
                                                            {{-- <div class="col-md-6">
        <div class="mb-3">
            <label for="plan_name" class="form-label">Plan Name<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="plan_name" name="plan_name" required>
            <div class="invalid-feedback" id="plan_nameError"></div>
        </div>
    </div> --}}
                                                            <div id="groupNameContainer">
                                                                <div class="row group-name-row col-md-6 mb-3">
                                                                    <div class="col-md-3">
                                                                        <label for="plan_name"
                                                                            class="form-label">Plan Name <span
                                                                                class="text-danger">*</span></label>
                                                                        <input type="text" class="form-control"
                                                                            name="plan_name[]" required
                                                                            placeholder="Enter Plan Name">
                                                                        <div class="invalid-feedback plan_nameError">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">
                                                                            Duration <span class="text-danger">*</span>
                                                                        </label>
                                                                        <input type="text"
                                                                            class="form-control"
                                                                            name="duration[]"
                                                                            placeholder="30 Days">
                                                                        <div class="invalid-feedback durationError"></div>
                                                                    </div>

                                                                    <div class="col-md-2">
                                                                        <label class="form-label">
                                                                            Price <span class="text-danger">*</span>
                                                                        </label>
                                                                        <input type="number"
                                                                            step="0.01"
                                                                            class="form-control"
                                                                            name="price[]"
                                                                            placeholder="100">
                                                                        <div class="invalid-feedback priceError"></div>
                                                                    </div>

                                                                    <div class="col-md-4">
                                                                        <label class="form-label">
                                                                            Description
                                                                        </label>
                                                                        <textarea class="form-control"
                                                                                name="description[]"
                                                                                rows="1"></textarea>
                                                                    </div>
                                                                    <div class="col-md-2 d-flex align-items-end">
                                                                        <button type="button"
                                                                            class="btn btn-success add-group-btn">+</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                                <button type="submit" class="btn btn-primary"
                                                                    id="submitBtn">Submit</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                @endif
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
        let editMode = false;

        function setFormToCreateMode() {
            editMode = false;
            $('#plan_form').removeAttr('data-mode');
            $('#companyTabLabel').text('New plan');
            $('#submitBtn').text('Submit');
            $('input[name="plan_id[]"]').remove();
            // Clear input fields
            $('.group-name-row:not(:first)').remove();
            $('.group-name-row:first input').val('');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        function setFormToEditMode(data) {
            editMode = true;
            $('#plan_form').attr('data-mode', 'edit');
            $('#companyTabLabel').text('Edit Plan');
            $('#submitBtn').text('Update');
            // Remove any old hidden fields then add new one
            $('input[name="plan_id[]"]').remove();
    $('#plan_form').append(
        `<input type="hidden" name="plan_id[]" value="${data.plan_id}">`
    );
            // Only single value
            $('input[name="plan_name[]"]').first().val(data.plan_name);
    $('input[name="duration[]"]').first().val(data.duration);
    $('input[name="price[]"]').first().val(data.price);
    $('textarea[name="description[]"]').first().val(data.description);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            // DataTable Init
             var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};
            var table = $('#planData').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('plan.list') }}",
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false,
                title: '#'
            },
            {
                data: 'plan_name',
                name: 'plan_name',
                title: 'Plan'
            },
            {
                data: 'duration',
                name: 'duration',
                title: 'Duration'
            },
            {
                data: 'price',
                name: 'price',
                title: 'Price'
            },
            {
                data: 'description',
                name: 'description',
                title: 'Description'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                title: 'Actions',
                 visible: canUpdate || canDelete
            }
        ],
        responsive: true
    });

            $('#refreshBtn').click(() => table.ajax.reload());

            // Add/remove group name field
            document.getElementById('groupNameContainer').addEventListener('click', function(e) {
                if (e.target.classList.contains('add-group-btn')) {
                    const newField = document.createElement('div');
                    newField.className = 'row group-name-row col-md-6 mb-3';
                    newField.innerHTML = `
                        <input type="hidden" name="plan_id[]">

                        <div class="col-md-3">
                            <label class="form-label">Plan Name</label>
                            <input type="text" class="form-control" name="plan_name[]">
                            <div class="invalid-feedback plan_nameError"></div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Duration</label>
                            <input type="text" class="form-control" name="duration[]">
                            <div class="invalid-feedback durationError"></div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" name="price[]">
                            <div class="invalid-feedback priceError"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description[]"></textarea>
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger remove-group-btn">
                                −
                            </button>
                        </div>
                        `;
                    this.appendChild(newField);
                }
                if (e.target.classList.contains('remove-group-btn')) {
                    e.target.closest('.group-name-row').remove();
                }
            });

            // Tab show event: update tab label etc on display
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');
                if (target === '#new-plan') {
                    if (editMode) {
                        $('#companyTabLabel').text('Edit Plan');
                        $('#submitBtn').text('Update');
                    } else {
                        setFormToCreateMode(); // will also update label etc.
                    }
                }
                // If switching to list, always reset form
                if (target === '#plan-list') {
                    setFormToCreateMode();
                }
            });

            // Handle edit button click
            $(document).on('click', '.edit-group', function() {
                const groupId = $(this).data('id');
                $('#submitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Loading...');
                $.ajax({
                    url: "{{ route('plan.edit', ':id') }}".replace(':id', groupId),
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response.status === 'success') {
                            setFormToEditMode(response.data);
                            $('a[href="#new-plan"]').tab('show');
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                            setFormToCreateMode();
                        }
                    },
                    error: function(xhr) {
                        iziToast.error({
                            title: 'Error',
                            message: xhr.responseJSON?.message ||
                                'Failed to fetch group data',
                            position: 'topRight'
                        });
                        setFormToCreateMode();
                    },
                    complete: function() {
                        $('#submitBtn').prop('disabled', false);
                    }
                });
            });

            // Handle delete
            $(document).on('click', '.delete-group', function() {
                const groupId = $(this).data('id');
                iziToast.question({
                    timeout: 20000,
                    close: false,
                    overlay: true,
                    displayMode: 'once',
                    id: 'question',
                    zindex: 999,
                    title: 'Confirm',
                    message: 'Are you sure you want to delete this Plan.?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({
                                transitionOut: 'fadeOut'
                            }, toast, 'button');
                            $.ajax({
                                url: "{{ route('plan.destroy') }}",
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    plan_id: groupId
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        iziToast.success({
                                            title: 'Success',
                                            message: response.message,
                                            position: 'topRight'
                                        });
                                        $('#planData').DataTable().ajax
                                        .reload();
                                    } else {
                                        iziToast.error({
                                            title: 'Error',
                                            message: response.message,
                                            position: 'topRight'
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    iziToast.error({
                                        title: 'Error',
                                        message: xhr.responseJSON
                                            ?.message ||
                                            'Failed to delete Status.',
                                        position: 'topRight'
                                    });
                                }
                            });
                        }, true],
                        ['<button>NO</button>', function(instance, toast) {
                            instance.hide({
                                transitionOut: 'fadeOut'
                            }, toast, 'button');
                        }]
                    ]
                });
            });

            // Handle form submission (add and edit)
            $('#plan_form').submit(function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
                let isValid = true;
                const plan_names = [];
                const durations = [];
                const prices = [];
                const descriptions = [];
                $('.group-name-row').each(function () {

                plan_names.push($(this).find('[name="plan_name[]"]').val());
                durations.push($(this).find('[name="duration[]"]').val());
                prices.push($(this).find('[name="price[]"]').val());
                descriptions.push($(this).find('[name="description[]"]').val());

                });
                if (!isValid) return;
                // Prepare form data
                const formData = {
                    plan_name: plan_names,
                    duration: durations,
                    price: prices,
                    description: descriptions,
                    _token: $('input[name="_token"]').val()
                };
                if (editMode) formData.plan_id = $('input[name="plan_id[]"]').val();
                $('#submitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Processing...');
                $.ajax({
                    url: "{{ route('plan.store') }}",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight',
                            timeout: 700,
                            onClosed: function() {
                                $('#planData').DataTable().ajax.reload();
                                $('a[href="#plan-list"]').tab('show');
                                setFormToCreateMode(); // reset after submit
                            }
                        });
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                if (field.startsWith('plan_name.')) {
                                    const index = field.split('.')[1];
                                    $('input[name="plan_name[]"]').eq(index).addClass(
                                            'is-invalid')
                                        .next('.invalid-feedback').text(errors[field][0])
                                    .show();
                                }
                            }
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: xhr.responseJSON?.message ||
                                    'Something went wrong',
                                position: 'topRight'
                            });
                        }
                    },
                    complete: function() {
                        $('#submitBtn').prop('disabled', false).html(editMode ? 'Update' :
                            'Submit');
                    }
                });
            });

            // Reset form handler: set back to create mode
            $('#plan_form').on('reset', function() {
                setFormToCreateMode();
            });

            // Real-time validation for plan_name fields (all, since dynamic)
            $(document).on('input', 'input[name="plan_name[]"]', function() {
                if ($(this).val().trim()) {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').hide();
                }
            });
        });
    </script>

</body>

</html>
