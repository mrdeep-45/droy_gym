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
                                                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#subscription-list">List</a>
                                            </li>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#new-subscription" id="companyTabLabel">New Subscription</a>
                                            </li>
                                        @endif
                                    </ul>
                                    <div class="tab-content">
                                        @if (permissions_check('canView'))
                                            <div class="tab-pane border-0 show active text-muted px-1" id="subscription-list" role="tabpanel">
                                                <table id="subscriptionData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Member</th>
                                                            <th>Plan</th>
                                                            <th>Trainer</th>
                                                            <th>Start</th>
                                                            <th>End</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <div class="tab-pane text-muted border-0 px-1" id="new-subscription" role="tabpanel">
                                                <form id="subscription_form" novalidate>
                                                    @csrf
                                                    <input type="hidden" name="subscription_id" id="subscription_id">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Member <span class="text-danger">*</span></label>
                                                            <select class="form-control" name="member_id" id="member_id">
                                                                <option value="">Select Member</option>
                                                                @foreach($members as $m)
                                                                    <option value="{{ $m->id }}">{{ $m->full_name }} ({{ $m->member_number }})</option>
                                                                @endforeach
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Plan <span class="text-danger">*</span></label>
                                                            <select class="form-control" name="plan_id" id="plan_id">
                                                                <option value="">Select Plan</option>
                                                                @foreach($plans as $p)
                                                                    <option value="{{ $p->plan_id }}" data-price="{{ $p->price }}" data-duration="{{ $p->duration }}">
                                                                        {{ $p->plan_name }} ({{ $p->duration }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Assign Trainer (Optional)</label>
                                                            <select class="form-control" name="trainer_id" id="trainer_id">
                                                                <option value="">None</option>
                                                                @foreach($trainers as $t)
                                                                    <option value="{{ $t->trainer_id }}">{{ $t->trainer_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="start_date" id="start_date">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">End Date (Auto)</label>
                                                            <input type="date" class="form-control" id="end_date_preview" readonly>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Amount Payable <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" class="form-control" name="amount_payable" id="amount_payable">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-3 mb-3" id="statusField" style="display:none;">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-control" name="status" id="status">
                                                                <option value="Active">Active</option>
                                                                <option value="Expired">Expired</option>
                                                                <option value="Canceled">Canceled</option>
                                                            </select>
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
            $('#companyTabLabel').text('New Subscription');
            $('#submitBtn').text('Submit');
            $('#subscription_form')[0].reset();
            $('#subscription_id').val('');
            $('#end_date_preview').val('');
            $('#statusField').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};

            var table = $('#subscriptionData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('subscription.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'member_name', name: 'member_name', title: 'Member' },
                    { data: 'plan_name', name: 'plan_name', title: 'Plan' },
                    { data: 'trainer_name', name: 'trainer_name', title: 'Trainer' },
                    { data: 'start_date', name: 'start_date', title: 'Start' },
                    { data: 'end_date', name: 'end_date', title: 'End' },
                    { data: 'amount_payable', name: 'amount_payable', title: 'Amount' },
                    { data: 'status_badge', name: 'status_badge', title: 'Status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions', visible: canUpdate || canDelete }
                ],
                responsive: true
            });

            // Auto-fill amount + preview end date when plan changes
            $('#plan_id').on('change', function() {
                const selected = $(this).find(':selected');
                const price = selected.data('price');
                if (price) $('#amount_payable').val(price);
                updateEndDatePreview();
            });

            $('#start_date').on('change', updateEndDatePreview);

            function updateEndDatePreview() {
                const planId = $('#plan_id').val();
                const startDate = $('#start_date').val();
                if (planId && startDate) {
                    $.get("{{ route('subscription.plandetails', ':id') }}".replace(':id', planId), function(response) {
                        if (response.status === 'success') {
                            // Server calculates the real end_date on submit; this is just a quick client preview
                            const duration = response.data.duration.toLowerCase();
                            let date = new Date(startDate);
                            const num = parseInt(duration.match(/\d+/)?.[0] || 1);
                            if (duration.includes('year')) date.setFullYear(date.getFullYear() + num);
                            else if (duration.includes('week')) date.setDate(date.getDate() + (num * 7));
                            else if (duration.includes('day')) date.setDate(date.getDate() + num);
                            else date.setMonth(date.getMonth() + num);
                            $('#end_date_preview').val(date.toISOString().split('T')[0]);
                        }
                    });
                }
            }

            // Edit
            $(document).on('click', '.edit-subscription', function() {
                const id = $(this).data('id');
                $.get("{{ route('subscription.edit', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        editMode = true;
                        $('#subscription_id').val(data.id);
                        $('#member_id').val(data.member_id);
                        $('#plan_id').val(data.plan_id);
                        $('#trainer_id').val(data.trainer_id);
                        $('#start_date').val(data.start_date);
                        $('#end_date_preview').val(data.end_date);
                        $('#amount_payable').val(data.amount_payable);
                        $('#status').val(data.status);
                        $('#statusField').show();
                        $('#companyTabLabel').text('Edit Subscription');
                        $('#submitBtn').text('Update');
                        $('a[href="#new-subscription"]').tab('show');
                    }
                });
            });

            // Cancel/Delete
            $(document).on('click', '.delete-subscription', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Cancel this subscription?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('subscription.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", subscription_id: id },
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
            $('#subscription_form').submit(function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();

                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('subscription.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight',
                            timeout: 700,
                            onClosed: function() {
                                table.ajax.reload();
                                $('a[href="#subscription-list"]').tab('show');
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
                if ($(e.target).attr('href') === '#subscription-list') setFormToCreateMode();
            });
        });
    </script>
</body>

</html>
