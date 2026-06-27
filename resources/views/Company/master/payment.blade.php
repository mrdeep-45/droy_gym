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
                                                <a class="nav-link active" data-bs-toggle="tab" href="#payment-list">List</a>
                                            </li>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#new-payment" id="companyTabLabel">New Payment</a>
                                            </li>
                                        @endif
                                    </ul>
                                    <div class="tab-content">
                                        @if (permissions_check('canView'))
                                            <div class="tab-pane border-0 show active text-muted px-1" id="payment-list" role="tabpanel">
                                                <table id="paymentData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Member</th>
                                                            <th>Plan</th>
                                                            <th>Amount Paid</th>
                                                            <th>Date</th>
                                                            <th>Method</th>
                                                            <th>Status</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <div class="tab-pane text-muted border-0 px-1" id="new-payment" role="tabpanel">
                                                <form id="payment_form" novalidate>
                                                    @csrf
                                                    <input type="hidden" name="payment_id" id="payment_id">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Subscription <span class="text-danger">*</span></label>
                                                            <select class="form-control" name="subscription_id" id="subscription_id">
                                                                <option value="">Select Member / Subscription</option>
                                                                @foreach($subscriptions as $s)
                                                                    <option value="{{ $s->id }}">
                                                                        {{ $s->member->full_name ?? '-' }} - {{ $s->plan->plan_name ?? '-' }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-8 mb-3">
                                                            <label class="form-label">Subscription Details</label>
                                                            <input type="text" class="form-control" id="subDetailsPreview" readonly placeholder="Select a subscription to see balance">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" class="form-control" name="amount_paid" id="amount_paid">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                                            <select class="form-control" name="payment_method" id="payment_method">
                                                                <option value="Cash">Cash</option>
                                                                <option value="Card">Card</option>
                                                                <option value="UPI">UPI</option>
                                                                <option value="Bank Transfer">Bank Transfer</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Transaction ID</label>
                                                            <input type="text" class="form-control" name="transaction_id" id="transaction_id">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Payment Status</label>
                                                            <select class="form-control" name="payment_status" id="payment_status">
                                                                <option value="Paid">Paid</option>
                                                                <option value="Partial">Partial</option>
                                                                <option value="Pending">Pending</option>
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
            $('#companyTabLabel').text('New Payment');
            $('#submitBtn').text('Submit');
            $('#payment_form')[0].reset();
            $('#payment_id').val('');
            $('#subDetailsPreview').val('');
            $('#payment_date').val(new Date().toISOString().split('T')[0]);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};

            var table = $('#paymentData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('payment.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'member_name', name: 'member_name', title: 'Member' },
                    { data: 'plan_name', name: 'plan_name', title: 'Plan' },
                    { data: 'amount_paid', name: 'amount_paid', title: 'Amount Paid' },
                    { data: 'payment_date', name: 'payment_date', title: 'Date' },
                    { data: 'payment_method', name: 'payment_method', title: 'Method' },
                    { data: 'status_badge', name: 'status_badge', title: 'Status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions', visible: canUpdate || canDelete }
                ],
                responsive: true
            });

            $('#subscription_id').on('change', function() {
                const id = $(this).val();
                if (!id) return;
                $.get("{{ route('payment.subdetails', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const d = response.data;
                        $('#subDetailsPreview').val(
                            `${d.member_name} | Plan: ${d.plan_name} | Payable: ${d.amount_payable} | Paid: ${d.total_paid} | Balance: ${d.balance}`
                        );
                        if (!editMode) $('#amount_paid').val(d.balance > 0 ? d.balance : '');
                    }
                });
            });

            // Edit
            $(document).on('click', '.edit-payment', function() {
                const id = $(this).data('id');
                $.get("{{ route('payment.edit', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        editMode = true;
                        $('#payment_id').val(data.id);
                        $('#subscription_id').val(data.subscription_id).trigger('change');
                        $('#amount_paid').val(data.amount_paid);
                        $('#payment_method').val(data.payment_method);
                        $('#transaction_id').val(data.transaction_id);
                        $('#payment_date').val(data.payment_date);
                        $('#payment_status').val(data.payment_status);
                        $('#companyTabLabel').text('Edit Payment');
                        $('#submitBtn').text('Update');
                        $('a[href="#new-payment"]').tab('show');
                    }
                });
            });

            // Delete
            $(document).on('click', '.delete-payment', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Delete this payment record?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('payment.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", payment_id: id },
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
            $('#payment_form').submit(function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('payment.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        iziToast.success({
                            title: 'Success', message: response.message, position: 'topRight', timeout: 700,
                            onClosed: function() {
                                table.ajax.reload();
                                $('a[href="#payment-list"]').tab('show');
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
                if ($(e.target).attr('href') === '#payment-list') setFormToCreateMode();
            });
        });
    </script>
</body>

</html>
