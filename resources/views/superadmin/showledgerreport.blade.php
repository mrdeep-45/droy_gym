<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient"
    data-menu-styles="light">

<head>
    @include('include/meta_tags')
    @include('include/header_links')
    @include('include/datatable_css_link')
<style>
  .kanban-container {
    overflow-x: auto;
    padding: 10px;
    scroll-behavior: smooth;
    -ms-overflow-style: none;
    scrollbar-width: thin;
    background-color: #f8fafc;
}


        .kanban-container::-webkit-scrollbar {
            display: none;
        }

.kanban-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.kanban-columns-container {
    min-height: calc(100vh - 350px);
    display: inline-flex;
    gap: 16px;
    width: 100%;
    align-items: flex-start;
}

 @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }


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
                                            <ul class="nav nav-pills justify-content-left nav-style-2 mb-1"
                                                role="tablist">
                                                
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#company-list"
                                                            aria-selected="true">Ledger Report </a>
                                                    </li>
                                               

                                                
                                                 
                                                   
   


                                               
                                            </ul>
                                            

                                            <div class="tab-content">
                                               
                                                    <div class="tab-pane border-0 show active text-muted px-1 "
                                                        id="company-list" role="tabpanel">       

              <form id="ledgerReportForm" method="GET" action="{{ route('showledgerreport.data') }}" target="_blank" class="form-inline w-100">
                <div class="row">
                <div class="col-md-2">
                    <select name="staff_id" id="staffFilter" class="form-control select2-filter">
                        <option value="">-- All Staff --</option>
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->staff_id }}">{{ $staff->staff_name }}</option>
                        @endforeach
                    </select>
                </div>
                &nbsp;
                <div class="col-md-2">
                    @php $currentYear = now()->year; @endphp
                    <select name="year" id="yearFilter" class="form-control">
                        @for($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
                            <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                &nbsp;
                <div class="col-md-2">
                    @php $currentMonth = date('n'); @endphp
                    <select name="month" id="monthFilter" class="form-control select2-filter">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                &nbsp;
                
                <div class="col-md-2">
                    <button type="submit" id="viewLedgerBtn" class="btn btn-primary">View Ledger</button>
                </div>

                <div class="col-md-2">
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" 
                                id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Export
                        </button>
                        <div class="dropdown-menu" aria-labelledby="exportDropdown">
                            <a class="dropdown-item" href="#" id="exportPdfBtn">Export to PDF</a>
                            <a class="dropdown-item" href="#" id="exportExcelBtn">Export to Excel</a> 
                        </div>
                    </div>
                </div>
				</div>
            </form>
        
   
                                                       
                                                       <!-- <div class="kanban-container"> -->
															<!-- <div class="kanban-columns-container w-100"> -->
															   
																	<table id="rawdata" class="table table-bordered text-nowrap" style="width: 100%;">
																		<thead>
																			<tr id="attendanceHeader"></tr>
																		</thead>
																		<tbody></tbody>
																	</table>
															  
															<!-- </div> -->
														<!-- </div>                                              -->
                                               
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
           
        

        @include('company/delete_modal')
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

    <!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

  <script>
// $(document).ready(function () {
//     // Initialize Select2 filters
//     $('#staffFilter, #monthFilter').select2({ width: '100%' });

//     // Handle the PDF Export button click
//     $('#exportPdfBtn').click(function(e) {
//         e.preventDefault();
//         // Submitting the form handles all filtering and opens the PDF in a new tab
//         $('#ledgerReportForm').submit(); 
//     });
// });
</script>
<script>
$(document).ready(function () {
    // Initialize Select2 filters
    $('#staffFilter, #monthFilter').select2({ width: '100%' });

    var ledgerTable = $('#rawdata').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // You are using external filters
        // Define the columns for the DataTable
        columns: [
            { data: 'staff_id', name: 'staff_id', title: 'ID' },
            { data: 'staff_name', name: 'staff_name', title: 'Name' },
            { data: 'role', name: 'role', title: 'Role' },
            { data: 'base_salary', name: 'base_salary', title: 'Base Salary' },
            { data: 'total_days_month', name: 'total_days_month', title: 'Total Days' },
            { data: 'present_days', name: 'present_days', title: 'Present' },
            { data: 'leave_days', name: 'leave_days', title: 'Leave' },
            { data: 'absent_days', name: 'absent_days', title: 'Absent' },
            { data: 'lwp_days', name: 'lwp_days', title: 'LWP' },
            { data: 'deduction_absent', name: 'deduction_absent', title: 'Deduction' },
            { data: 'net_payable', name: 'net_payable', title: 'Net Payable' },
            // Add more columns here to match your ledgerData keys
        ],
        // Initial setup with no data
        ajax: {
            url: "{{ route('showledgerreport.data') }}",
            type: 'GET',
            data: function (d) {
                // Pass filters to the server-side DataTable AJAX request
                d.staff_id = $('#staffFilter').val();
                d.year = $('#yearFilter').val();
                d.month = $('#monthFilter').val();
            }
        },
        // IMPORTANT: By default, don't load data
        deferLoading: 0, 
    });

    // 1. Handle "View Ledger" Button Click
    $('#ledgerReportForm').on('submit', function(e) {
        e.preventDefault();
        
        // Check if the form's current action is for the DataTable route
        if ($(this).attr('action') === "{{ route('showledgerreport.data') }}") {
            // Load the data into the DataTable
            ledgerTable.ajax.reload();
        } else {
            // If the action was changed for export, submit the form normally
            this.submit();
        }
    });

    // 2. Handle PDF Export Button Click
    $('#exportPdfBtn').click(function(e) {
        e.preventDefault();
        var form = $('#ledgerReportForm');
        // Change the form action for PDF export
        form.attr('action', "{{ route('showledgerreport.pdf') }}");
        form.attr('target', '_blank'); // Open in new tab
        // Now submit the form, which will hit the new action
        form.submit();
        // IMPORTANT: Reset the form action back to the DataTable route
        form.attr('action', "{{ route('showledgerreport.data') }}");
        form.removeAttr('target');
    });

    // 3. Handle Excel Export Button Click
    $('#exportExcelBtn').click(function(e) {
        e.preventDefault();
        var form = $('#ledgerReportForm');
        // Change the form action for Excel export
        form.attr('action', "{{ route('showledgerreport.excel') }}");
        form.removeAttr('target'); // Download will happen in the current tab
        // Now submit the form, which will hit the new action
        form.submit();
        // IMPORTANT: Reset the form action back to the DataTable route
        form.attr('action', "{{ route('showledgerreport.data') }}");
    });
});
</script>



</body>

</html>
