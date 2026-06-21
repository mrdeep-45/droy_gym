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
                                            <ul class="nav nav-pills justify-content-left nav-style-2 mb-1" role="tablist">
                                                @if (permissions_check('canView'))
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#member-list" aria-selected="true">List</a>
                                                    </li>
                                                @endif
                                                @if (permissions_check('canCreate'))
                                                    <li class="nav-item">
                                                        <a class="nav-link" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#new-member" aria-selected="false"
                                                            id="companyTabLabel">New Member</a>
                                                    </li>
                                                @endif
                                            </ul>
                                            <div class="tab-content">
                                                @if (permissions_check('canView'))
                                                    <div class="tab-pane border-0 show active text-muted px-1" id="member-list" role="tabpanel">
                                                        <div class="row">
                                                            <table id="memberData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:1%;">#</th>
                                                                        <th>Photo</th>
                                                                        <th>Member No.</th>
                                                                        <th>Name</th>
                                                                        <th>Phone</th>
                                                                        <th>Email</th>
                                                                        <th>Joining Date</th>
                                                                        <th>Status</th>
                                                                        <th class="text-center">Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody></tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (permissions_check('canCreate'))
                                                    <div class="tab-pane text-muted border-0 px-1" id="new-member" role="tabpanel">
                                                        <form id="member_form" enctype="multipart/form-data" novalidate>
                                                            @csrf
                                                            <input type="hidden" name="member_id" id="member_id">
                                                            <div class="row">
                                                                <div class="col-md-4 mb-3">
                                                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" name="full_name" id="full_name">
                                                                    <div class="invalid-feedback"></div>
                                                                </div>
                                                                <div class="col-md-4 mb-3">
                                                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" name="phone" id="phone">
                                                                    <div class="invalid-feedback"></div>
                                                                </div>
                                                                <div class="col-md-4 mb-3">
                                                                    <label class="form-label">Email</label>
                                                                    <input type="email" class="form-control" name="email" id="email">
                                                                    <div class="invalid-feedback"></div>
                                                                </div>
                                                                <div class="col-md-3 mb-3">
                                                                    <label class="form-label">Gender</label>
                                                                    <select class="form-control" name="gender" id="gender">
                                                                        <option value="">Select</option>
                                                                        <option value="Male">Male</option>
                                                                        <option value="Female">Female</option>
                                                                        <option value="Other">Other</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3 mb-3">
                                                                    <label class="form-label">Date of Birth</label>
                                                                    <input type="date" class="form-control" name="dob" id="dob">
                                                                </div>
                                                                <div class="col-md-3 mb-3">
                                                                    <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                                                                    <input type="date" class="form-control" name="joining_date" id="joining_date">
                                                                    <div class="invalid-feedback"></div>
                                                                </div>
                                                                <div class="col-md-3 mb-3" id="statusField" style="display:none;">
                                                                    <label class="form-label">Status</label>
                                                                    <select class="form-control" name="status" id="status">
                                                                        <option value="Active">Active</option>
                                                                        <option value="Inactive">Inactive</option>
                                                                        <option value="Suspended">Suspended</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Photo</label>
                                                                    <input type="file" class="form-control" name="m_photo" id="m_photo">
                                                                    <img id="previewPhoto" width="80" class="mt-2 d-none">
                                                                </div>
                                                            </div>
                                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                                <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
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
            $('#member_form').removeAttr('data-mode');
            $('#companyTabLabel').text('New Member');
            $('#submitBtn').text('Submit');
            $('#member_form')[0].reset();
            $('#member_id').val('');
            $('#statusField').hide();
            $('#previewPhoto').addClass('d-none');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};

            var table = $('#memberData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('member.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'photo', name: 'photo', title: 'Photo', orderable: false },
                    { data: 'member_number', name: 'member_number', title: 'Member No.' },
                    { data: 'full_name', name: 'full_name', title: 'Name' },
                    { data: 'phone', name: 'phone', title: 'Phone' },
                    { data: 'email', name: 'email', title: 'Email' },
                    { data: 'joining_date', name: 'joining_date', title: 'Joining Date' },
                    { data: 'status_badge', name: 'status_badge', title: 'Status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions', visible: canUpdate || canDelete }
                ],
                responsive: true
            });

            $('#refreshBtn').click(() => table.ajax.reload());

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');
                if (target === '#new-member' && !editMode) {
                    setFormToCreateMode();
                }
                if (target === '#member-list') {
                    setFormToCreateMode();
                }
            });

            // Edit
            $(document).on('click', '.edit-member', function() {
                const id = $(this).data('id');
                $.get("{{ route('member.edit', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        editMode = true;
                        $('#member_id').val(data.id);
                        $('#full_name').val(data.full_name);
                        $('#phone').val(data.phone);
                        $('#email').val(data.email);
                        $('#gender').val(data.gender);
                        $('#dob').val(data.dob);
                        $('#joining_date').val(data.joining_date);
                        $('#status').val(data.status);
                        $('#statusField').show();
                        if (data.m_photo) {
                            $('#previewPhoto').removeClass('d-none').attr('src', '/uploads/members/' + data.m_photo);
                        }
                        $('#companyTabLabel').text('Edit Member');
                        $('#submitBtn').text('Update');
                        $('a[href="#new-member"]').tab('show');
                    }
                });
            });

            // Delete
            $(document).on('click', '.delete-member', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Are you sure you want to remove this member?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('member.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", member_id: id },
                                success: function(response) {
                                    iziToast.success({ title: 'Success', message: response.message, position: 'topRight' });
                                    table.ajax.reload();
                                }
                            });
                        }, true],
                        ['<button>NO</button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }]
                    ]
                });
            });

            // Submit
            $('#member_form').submit(function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();

                let formData = new FormData(this);
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('member.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight',
                            timeout: 700,
                            onClosed: function() {
                                table.ajax.reload();
                                $('a[href="#member-list"]').tab('show');
                                setFormToCreateMode();
                            }
                        });
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                $('#' + field).addClass('is-invalid').next('.invalid-feedback').text(errors[field][0]).show();
                            }
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: xhr.responseJSON?.message || 'Something went wrong',
                                position: 'topRight'
                            });
                        }
                    },
                    complete: function() {
                        $('#submitBtn').prop('disabled', false).html(editMode ? 'Update' : 'Submit');
                    }
                });
            });
        });
    </script>
</body>

</html>
