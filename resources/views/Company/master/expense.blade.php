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
                                                <a class="nav-link active" data-bs-toggle="tab" href="#expense-list">List</a>
                                            </li>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#new-expense" id="companyTabLabel">New Expense</a>
                                            </li>
                                        @endif
                                    </ul>
                                    <div class="tab-content">
                                        @if (permissions_check('canView'))
                                            <div class="tab-pane border-0 show active text-muted px-1" id="expense-list" role="tabpanel">
                                                <table id="expenseData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Title</th>
                                                            <th>Category</th>
                                                            <th>Amount</th>
                                                            <th>Date</th>
                                                            <th>Paid To</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        @endif
                                        @if (permissions_check('canCreate'))
                                            <div class="tab-pane text-muted border-0 px-1" id="new-expense" role="tabpanel">
                                                <form id="expense_form" novalidate>
                                                    @csrf
                                                    <input type="hidden" name="expense_id" id="expense_id">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Expense Title <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="title" id="title">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                                            <select class="form-control" name="category" id="category">
                                                                <option value="Rent">Rent</option>
                                                                <option value="Utilities">Utilities</option>
                                                                <option value="Maintenance">Maintenance</option>
                                                                <option value="Salaries">Salaries</option>
                                                                <option value="Marketing">Marketing</option>
                                                                <option value="Supplements Stock">Supplements Stock</option>
                                                                <option value="Cleaning">Cleaning</option>
                                                                <option value="Other">Other</option>
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" class="form-control" name="amount" id="amount">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="expense_date" id="expense_date" value="{{ date('Y-m-d') }}">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Paid To / Vendor</label>
                                                            <input type="text" class="form-control" name="paid_to" id="paid_to">
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Notes</label>
                                                            <textarea class="form-control" name="note" id="note" rows="1"></textarea>
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
            $('#companyTabLabel').text('New Expense');
            $('#submitBtn').text('Submit');
            $('#expense_form')[0].reset();
            $('#expense_id').val('');
            $('#expense_date').val(new Date().toISOString().split('T')[0]);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};

            var table = $('#expenseData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('expense.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'title', name: 'title', title: 'Title' },
                    { data: 'category', name: 'category', title: 'Category' },
                    { data: 'amount', name: 'amount', title: 'Amount' },
                    { data: 'expense_date', name: 'expense_date', title: 'Date' },
                    { data: 'paid_to', name: 'paid_to', title: 'Paid To', defaultContent: '-' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions', visible: canUpdate || canDelete }
                ],
                responsive: true
            });

            $(document).on('click', '.edit-expense', function() {
                const id = $(this).data('id');
                $.get("{{ route('expense.edit', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        editMode = true;
                        $('#expense_id').val(data.id);
                        $('#title').val(data.title);
                        $('#category').val(data.category);
                        $('#amount').val(data.amount);
                        $('#expense_date').val(data.expense_date);
                        $('#paid_to').val(data.paid_to);
                        $('#note').val(data.note);
                        $('#companyTabLabel').text('Edit Expense');
                        $('#submitBtn').text('Update');
                        $('a[href="#new-expense"]').tab('show');
                    }
                });
            });

            $(document).on('click', '.delete-expense', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Delete this expense record?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('expense.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", expense_id: id },
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

            $('#expense_form').submit(function(e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('expense.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        iziToast.success({
                            title: 'Success', message: response.message, position: 'topRight', timeout: 700,
                            onClosed: function() {
                                table.ajax.reload();
                                $('a[href="#expense-list"]').tab('show');
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
                if ($(e.target).attr('href') === '#expense-list') setFormToCreateMode();
            });
        });
    </script>
</body>

</html>
