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
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#scan-feed">Check-In / Check-Out</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#attendance-list">Today's Log</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane border-0 show active text-muted px-1" id="scan-feed" role="tabpanel">
                                            <div class="row justify-content-center">
                                                <div class="col-md-6 mt-4">
                                                    <div class="text-center mb-3">
                                                        <i class="fas fa-id-card fa-3x text-primary"></i>
                                                        <h4 class="mt-2">Scan or type Member ID</h4>
                                                    </div>
                                                    <form id="scan_form">
                                                        <input type="text" id="member_number" class="form-control form-control-lg text-center"
                                                            placeholder="GYM-1001" autofocus autocomplete="off">
                                                    </form>
                                                    <div id="scanResult" class="mt-4 text-center fs-4"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane text-muted border-0 px-1" id="attendance-list" role="tabpanel">
                                            <table id="attendanceData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Member No.</th>
                                                        <th>Name</th>
                                                        <th>Check In</th>
                                                        <th>Check Out</th>
                                                        <th>Duration</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
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
            var table = $('#attendanceData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('attendance.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'member_number', name: 'member_number', title: 'Member No.' },
                    { data: 'member_name', name: 'member_name', title: 'Name' },
                    { data: 'check_in', name: 'check_in', title: 'Check In' },
                    { data: 'check_out', name: 'check_out', title: 'Check Out', defaultContent: '-' },
                    { data: 'duration', name: 'duration', title: 'Duration', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions' }
                ],
                responsive: true
            });

            // Scanner input: press Enter after scan/type to submit
            $('#member_number').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    submitScan();
                }
            });

            function submitScan() {
                const memberNumber = $('#member_number').val().trim();
                if (!memberNumber) return;

                $.ajax({
                    url: "{{ route('attendance.checkin') }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}", member_number: memberNumber },
                    success: function(response) {
                        const color = response.type === 'check_in' ? 'success' : 'info';
                        $('#scanResult').html(`<span class="text-${color}"><i class="fas fa-check-circle"></i> ${response.message}</span>`);
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 403) {
                            $('#scanResult').html(`<span class="text-danger fw-bold"><i class="fas fa-ban"></i> ${xhr.responseJSON.message}</span>`);
                        } else {
                            $('#scanResult').html(`<span class="text-danger">${xhr.responseJSON?.message || 'Member not found'}</span>`);
                        }
                    },
                    complete: function() {
                        $('#member_number').val('').focus();
                    }
                });
            }

            // Delete
            $(document).on('click', '.delete-attendance', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Remove this attendance record?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('attendance.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", attendance_id: id },
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
        });
    </script>
</body>

</html>
