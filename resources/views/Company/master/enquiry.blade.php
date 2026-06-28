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
                        <div class="col card-background-mt flex-fill">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <ul class="nav nav-pills justify-content-left nav-style-2 mb-1" role="tablist">
                                        @if (permissions_check('canView'))
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" href="#enquiry-list">List</a>
                                            </li>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#new-enquiry" id="companyTabLabel">New Enquiry</a>
                                            </li>
                                        @endif
                                    </ul>
                                    <div class="tab-content">
                                        @if (permissions_check('canView'))
                                            <div class="tab-pane border-0 show active text-muted px-1" id="enquiry-list" role="tabpanel">
                                                <table id="enquiryData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>Phone</th>
                                                            <th>Source</th>
                                                            <th>Status</th>
                                                            <th>Follow-Up</th>
                                                            <th>Remarks</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <div class="tab-pane text-muted border-0 px-1" id="new-enquiry" role="tabpanel">
                                                <form id="enquiry_form" novalidate>
                                                    @csrf
                                                    <input type="hidden" name="enquiry_id" id="enquiry_id">
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
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Enquiry Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="enquiry_date" id="enquiry_date" value="{{ date('Y-m-d') }}">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Source <span class="text-danger">*</span></label>
                                                            <select class="form-control" name="source" id="source">
                                                                <option value="Walk-In">Walk-In</option>
                                                                <option value="Instagram">Instagram</option>
                                                                <option value="Facebook">Facebook</option>
                                                                <option value="Friend Referral">Friend Referral</option>
                                                                <option value="Leaflet">Leaflet</option>
                                                                <option value="Other">Other</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-control" name="status" id="status">
                                                                <option value="Pending">Pending</option>
                                                                <option value="Converted">Converted (Joined)</option>
                                                                <option value="Lost">Lost</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Next Follow-Up Date</label>
                                                            <input type="date" class="form-control" name="follow_up_date" id="follow_up_date">
                                                        </div>
                                                        <div class="col-md-12 mb-3">
                                                            <label class="form-label">Staff Remarks</label>
                                                            <textarea class="form-control" name="remarks" id="remarks" rows="2"
                                                                placeholder="e.g., Wants weight loss, thinks price is high"></textarea>
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
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script>
        let editMode = false;

        function setFormToCreateMode() {
            editMode = false;
            $('#companyTabLabel').text('New Enquiry');
            $('#submitBtn').text('Submit');
            $('#enquiry_form')[0].reset();
            $('#enquiry_id').val('');
            $('#enquiry_date').val(new Date().toISOString().split('T')[0]);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};

            var table = $('#enquiryData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('enquiry.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'full_name', name: 'full_name', title: 'Name' },
                    { data: 'phone', name: 'phone', title: 'Phone' },
                    { data: 'source', name: 'source', title: 'Source' },
                    { data: 'status_badge', name: 'status_badge', title: 'Status' },
                    { data: 'follow_up_alert', name: 'follow_up_alert', title: 'Follow-Up' },
                    { data: 'remarks', name: 'remarks', title: 'Remarks', defaultContent: '-' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions', visible: canUpdate || canDelete }
                ],
                responsive: true
            });

            $(document).on('click', '.edit-enquiry', function() {
                const id = $(this).data('id');
                $.get("{{ route('enquiry.edit', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        editMode = true;
                        $('#enquiry_id').val(data.id);
                        $('#full_name').val(data.full_name);
                        $('#phone').val(data.phone);
                        $('#email').val(data.email);
                        $('#enquiry_date').val(data.enquiry_date);
                        $('#source').val(data.source);
                        $('#status').val(data.status);
                        $('#follow_up_date').val(data.follow_up_date);
                        $('#remarks').val(data.remarks);
                        $('#companyTabLabel').text('Edit Enquiry');
                        $('#submitBtn').text('Update');
                        $('a[href="#new-enquiry"]').tab('show');
                    }
                });
            });

            $(document).on('click', '.delete-enquiry', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Delete this enquiry?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('enquiry.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", enquiry_id: id },
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

            $('#enquiry_form').submit(function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('enquiry.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        iziToast.success({
                            title: 'Success', message: response.message, position: 'topRight', timeout: 700,
                            onClosed: function() {
                                table.ajax.reload();
                                $('a[href="#enquiry-list"]').tab('show');
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
                            iziToast.error({ title: 'Error', message: xhr.responseJSON?.message || 'Something went wrong', position: 'topRight' });
                        }
                    },
                    complete: function() {
                        $('#submitBtn').prop('disabled', false).html(editMode ? 'Update' : 'Submit');
                    }
                });
            });

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#enquiry-list') setFormToCreateMode();
            });
        });
    </script>
</body>

</html>
