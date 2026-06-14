<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient"
    data-menu-styles="light">

<head>
    @include('include/meta_tags')
    @include('include/header_links')
    @include('include/datatable_css_link')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Add your custom styles if needed */
        .select2-container .select2-selection--single { height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; }
    </style>
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
                                            <h4 class="mb-4">{{ $page_title }}</h4>
                                            
                                            <form id="attendanceReportForm" method="GET" action="{{ route('showDetailedAttendanceReport.data') }}" class="form-inline w-100 mb-4">
                                                <div class="row g-3 align-items-end">
                                                    <div class="col-md-3">
                                                        <label for="staffFilter" class="form-label">Staff Name</label>
                                                        <select name="staff_id" id="staffFilter" class="form-control select2-filter">
                                                            <option value="">-- All Staff --</option>
                                                            @foreach($staffList as $staff)
                                                                <option value="{{ $staff->staff_id }}">{{ $staff->staff_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label for="yearFilter" class="form-label">Year</label>
                                                        @php $currentYear = now()->year; @endphp
                                                        <select name="year" id="yearFilter" class="form-control">
                                                            @for($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
                                                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label for="monthFilter" class="form-label">Month</label>
                                                        @php $currentMonth = date('n'); @endphp
                                                        <select name="month" id="monthFilter" class="form-control select2-filter">
                                                            @for($m = 1; $m <= 12; $m++)
                                                                <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" id="viewReportBtn" class="btn btn-primary w-100">View Report</button>
                                                    </div>
                                                    <!-- <div class="col-md-3">
                                                        <div class="dropdown">
                                                            <button class="btn btn-success dropdown-toggle w-100" type="button" 
                                                                    id="exportDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                Export
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="exportDropdown">
                                                                <a class="dropdown-item" href="#" id="exportPdfBtn">Export to PDF</a>
                                                                <a class="dropdown-item" href="#" id="exportExcelBtn">Export to Excel</a> 
                                                            </div>
                                                        </div>
                                                    </div> -->
                                                </div>
                                            </form>
                                            
                                            <div class="table-responsive mt-4">
                                                <table id="attendance-data-table" class="table table-bordered text-nowrap" style="width: 100%;">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Staff Name</th>
                                                            <th>Time In</th>
                                                            <th>Time Out</th>
                                                            <th>Late Duration</th>
                                                            <th>Forgot Out?</th>
                                                            <th>In Image</th>
                                                            <th>Out Image</th>
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
        </div>
        
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 filters
            $('#staffFilter, #monthFilter').select2({ width: '100%' });

            var attendanceTable = $('#attendance-data-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false, // Use external filters
                columns: [
                    { data: 'date', name: 'date', title: 'Date' },
                    { data: 'staff_name', name: 'staff_name', title: 'Staff Name' },
                    { data: 'time_in', name: 'time_in', title: 'Time In' },
                    { data: 'time_out', name: 'time_out', title: 'Time Out' },
                    { data: 'late_duration', name: 'late_duration', title: 'Late Duration' },
                    { data: 'forgot_out_status', name: 'forgot_out_status', title: 'Forgot Out?' },
                    { data: 'face_image_in_url', name: 'face_image_in_url', title: 'In Image', orderable: false, searchable: false },
                    { data: 'face_image_out_url', name: 'face_image_out_url', title: 'Out Image', orderable: false, searchable: false },
                ],
                ajax: {
                    url: "{{ route('showDetailedAttendanceReport.data') }}",
                    type: 'GET',
                    data: function (d) {
                        // Pass filters to the server-side DataTable AJAX request
                        d.staff_id = $('#staffFilter').val();
                        d.year = $('#yearFilter').val();
                        d.month = $('#monthFilter').val();
                    }
                },
                // IMPORTANT: By default, don't load data until View Report is clicked
                deferLoading: 0, 
                order: [[0, 'asc']] // Sort by Date
            });

            // 1. Handle "View Report" Button Click
            $('#attendanceReportForm').on('submit', function(e) {
                e.preventDefault();
                
                // If the form's action is the DataTables route, reload the table
                if ($(this).attr('action') === "{{ route('showDetailedAttendanceReport.data') }}") {
                    attendanceTable.ajax.reload();
                } else {
                    // If the action was changed for export, submit the form normally
                    this.submit();
                }
            });

            // --- Note: You must implement the export endpoints for these buttons to work ---

            // 2. Handle PDF Export Button Click
            $('#exportPdfBtn').click(function(e) {
                e.preventDefault();
                var form = $('#attendanceReportForm');
                // Temporarily change the form action for PDF export
                // You need to create a route/method named 'showDetailedAttendanceReport.pdf' in your controller
                form.attr('action', "{{ route('showDetailedAttendanceReport.pdf') ?? '#' }}");
                form.attr('target', '_blank'); // Open in new tab
                form.submit();
                // Reset the form action back to the DataTable route
                form.attr('action', "{{ route('showDetailedAttendanceReport.data') }}");
                form.removeAttr('target');
            });

            // 3. Handle Excel Export Button Click
            $('#exportExcelBtn').click(function(e) {
                e.preventDefault();
                var form = $('#attendanceReportForm');
                // Temporarily change the form action for Excel export
                // You need to create a route/method named 'showDetailedAttendanceReport.excel' in your controller
                form.attr('action', "{{ route('showDetailedAttendanceReport.excel') ?? '#' }}");
                form.removeAttr('target');
                form.submit();
                // Reset the form action back to the DataTable route
                form.attr('action', "{{ route('showDetailedAttendanceReport.data') }}");
            });
        });
    </script>

</body>
</html>