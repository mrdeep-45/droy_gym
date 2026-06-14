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
                                                            aria-selected="true">Attendance View List</a>
                                                    </li>
                                               

                                                
                                                    <li class="nav-item ">
                                                       
                                                            <!-- <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" id="companyTabLabel1" href="#new-unit" aria-selected="false">New Unit</a>-->
                                                             <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#new-salary" aria-selected="false">Salary</a>
                                                    </li>
                                                   
   


                                               
                                            </ul>
                                            

                                            <div class="tab-content">
                                               
                                                    <div class="tab-pane border-0 show active text-muted px-1 "
                                                        id="company-list" role="tabpanel">
                                                        <div class="row">

                                            
                                             <div class="col-md-2">
        <select id="staffFilter" class="form-control select2-filter">
            <option value="">All</option>
            @foreach($staffList as $staff)
                <option value="{{ $staff->staff_id }}">{{ $staff->staff_name }}</option>
            @endforeach
        </select>
    </div>
    &nbsp;
    <div class="col-md-2">
        @php
$currentYear = now()->year;
@endphp
        <select id="yearFilter" class="form-control">
           @for($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
    <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
@endfor
        </select>
    </div>
    &nbsp;
    <div class="col-md-2">
       @php
    $currentMonth = date('n'); // Gets current month as number (1–12)
@endphp

<select id="monthFilter" class="form-control select2-filter">
    @for($m = 1; $m <= 12; $m++)
        <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
            {{ DateTime::createFromFormat('!m', $m)->format('F') }}
        </option>
    @endfor
</select>

    </div>
    &nbsp;
        
            <div class="col-md-2">
                <button id="viewBtn" class="btn btn-primary">View</button>
            </div>

            <div class="col-md-2">
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" 
                            id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Export
                    </button>
                    <div class="dropdown-menu" aria-labelledby="exportDropdown">
                        <a class="dropdown-item" href="#" id="exportExcelBtn">Export to Excel</a>
                        <a class="dropdown-item" href="#" id="exportPdfBtn">Export to PDF</a>
                    </div>
                </div>
            </div>
        
    </div>
                                                       
                                                       <div class="kanban-container">
    <div class="kanban-columns-container w-100">
       
            <table id="rawdata" class="table table-bordered text-nowrap" style="width: 100%;">
                <thead>
                    <tr id="attendanceHeader"></tr>
                </thead>
                <tbody></tbody>
            </table>
      
    </div>
</div>

                                                    
                                               
                                               
                                            </div>
                                             <div class="tab-pane text-muted border-0 px-1 " id="new-salary" role="tabpanel">
                                                   <!-- Filter Section -->
                                                <!-- Inside "Salary" tab -->
<div class="row mb-3">
    <div class="col-md-3">
        <select id="salary_staff_id" class="form-control select2-filter">
            <option value="">All Staff</option>
            @foreach($staffList as $staff)
                <option value="{{ $staff->staff_id }}">{{ $staff->staff_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
       
        <select id="salary_year" class="form-control select2-filter">
   @php
$currentYear1 = now()->year;
@endphp
            @for ($i = $currentYear1 - 1; $i <= $currentYear1 + 1; $i++)
                <option value="{{ $i }}" {{ $i == $currentYear1 ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-2">
        <select id="salary_month" class="form-control select2-filter">
            @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ $i == now()->month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->format('F') }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-2">
        <button onclick="loadSalaryData()" class="btn btn-primary">View</button>
    </div>
</div>



                                                    <br>

    <!-- Grid Table -->
    <table class="table table-bordered" id="salaryTable">
    <thead>
        <tr>
            <th>Staff Name</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Leave</th>
            <th>Week Off</th>
            <th>Base Salary</th>
            <th>Final Salary</th>
             <th>Status</th> 
            <th>Action</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>


</div>

<!-- Salary Slip Modal -->
<div class="modal fade" id="salaryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content" id="salaryDetails">
      <!-- Loaded dynamically by JS -->
       <style>
    .modal-header {
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
  padding: 20px 30px;
    }
    .modal-title {
    font-weight: bold;
    color: #333;
    font-size: 1.5rem;
    }
    .modal-dialog {
        max-width: 700px; /* Increase width */
        width: 90%; /* Responsive width */
    }

  #salaryDetails {
    min-height: 400px; /* Increase height */
    padding: 25px 30px;
    font-size: 1.1rem; /* Larger font size */
    line-height: 1.5;
  }
/* Optional: increase button size */
  .modal-footer .btn {
    font-size: 1.1rem;
    padding: 10px 20px;
  }

</style>
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
                </div>
            </div>
            <div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-labelledby="attendanceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceDetailModalLabel">Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">Loading details...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
$(document).ready(function () {
    var table;

    function loadAttendanceData() {
        var staff_id = $('#staffFilter').val();
        var year = $('#yearFilter').val();
        var month = $('#monthFilter').val();

        $.ajax({
            url: "{{ route('attendance.data') }}",
            data: {
                staff_id: staff_id,
                year: year,
                month: month
            },
            success: function (response) {
                if (table) {
                    table.destroy();
                    $('#rawdata tbody').empty();
                    $('#attendanceHeader').html('');
                }

                if (response.data.length > 0) {
                    var firstRow = response.data[0];
                    var columns = [];

                    Object.keys(firstRow).forEach(function (key) {
                        var label = key === 'staff_name' 
                            ? 'Staff Name' 
                            : moment(key).format('D') + '<br>(' + moment(key).format('ddd') + ')';

                        $('#attendanceHeader').append('<th>' + label + '</th>');

                        columns.push({ data: key, name: key, orderable: false, searchable: false });
                    });

                    table = $('#rawdata').DataTable({
                        data: response.data,
                        columns: columns,
                        language: {
                            emptyTable: 'No data available for selected filters'
                        }
                    });
                } else {
                    $('#attendanceHeader').html('<th>No Data</th>');
                }
            },
            error: function (xhr) {
                console.error(xhr.responseJSON);
            }
        });
    }

    // Bind filters and buttons
    $('#viewBtn').click(loadAttendanceData);

    $('#staffFilter, #yearFilter, #monthFilter').select2({ width: '100%' });

    // Optional: auto-refresh on year/month change
    $('#yearFilter, #monthFilter').on('change', function () {
        $('#viewBtn').trigger('click');
    });

    // Initial data load on page load
    $('#viewBtn').trigger('click');

        $('#exportExcelBtn').click(function(e) {
        e.preventDefault();
        exportAttendance('excel');
    });

    $('#exportPdfBtn').click(function(e) {
        e.preventDefault();
        exportAttendance('pdf');
    });

    function exportAttendance(type) {
        var staff_id = $('#staffFilter').val();
        var year = $('#yearFilter').val();
        var month = $('#monthFilter').val();
        
        var url = '';
        if (type === 'excel') {
            // Use the new route for Excel
            url = "{{ route('attendance.export.excel') }}";
        } else if (type === 'pdf') {
            // Use the new route for PDF
            url = "{{ route('attendance.export.pdf') }}";
        }
        
        // Construct the final URL with query parameters
        window.open(url + '?staff_id=' + staff_id + '&year=' + year + '&month=' + month, '_blank');
    }
        $('#rawdata tbody').on('click', '.attendance-detail-btn', function () {
        var staffId = $(this).data('staff-id');
        var date = $(this).data('date');
        var staffName = $(this).closest('tr').find('td:first').text(); // Get staff name from first cell

        $('#attendanceDetailModalLabel').text('Attendance Details for ' + staffName + ' on ' + moment(date).format('D MMM YYYY'));
        $('#modalContent').html('Loading details...'); // Reset content

        // AJAX call to fetch the specific attendance record
        $.ajax({
            url: "{{ route('attendance.get.detail') }}",
            data: {
                staff_id: staffId,
                date: date
            },
            success: function (response) {
                // Build the HTML content for the modal body
                if (response.attendance) {
                    var att = response.attendance;
                    var html = `
                        <table class="table table-sm">
                            <tr><th>Time In:</th><td>${att.time_in ? moment(att.time_in).format('HH:mm:ss') : 'N/A'}</td></tr>
                            <tr><th>Time Out:</th><td>${att.time_out ? moment(att.time_out).format('HH:mm:ss') : 'N/A'}</td></tr>
                            <tr><th>Lunch Out:</th><td>${att.lunch_out ? moment(att.lunch_out).format('HH:mm:ss') : 'N/A'}</td></tr>
                            <tr><th>Lunch In:</th><td>${att.lunch_in ? moment(att.lunch_in).format('HH:mm:ss') : 'N/A'}</td></tr>
                            <tr><th>Status:</th><td>${att.status}</td></tr>
                            
                            <tr><th>Remark:</th><td>${att.remark || 'None'}</td></tr>
                        </table>
                    `;
                    $('#modalContent').html(html);
                } else {
                    $('#modalContent').html('<p>No detailed attendance record found for this date.</p>');
                }
            },
            error: function () {
                $('#modalContent').html('<p class="text-danger">Error fetching attendance details.</p>');
            }
        });

        // The modal is automatically shown by the data-bs-target attribute on the button,
        // but you can call it manually if needed:
        // $('#attendanceDetailModal').modal('show');
    });
});


</script>
<script>
   
function loadSalaryData() {
    let staff_id = $('#salary_staff_id').val();
    let month = $('#salary_month').val();
    let year = $('#salary_year').val();

    if ($.fn.DataTable.isDataTable('#salaryTable')) {
        $('#salaryTable').DataTable().destroy();
    }

    $('#salaryTable').DataTable({
        processing: true,
        serverSide: false, // You can set true if querying DB directly
        ajax: {
            url: "{{ route('salary.data') }}",
            data: { staff_id, month, year }
        },
        columns: [
            { data: 'staff_name', name: 'staff_name' },
            { data: 'present', name: 'present' },
            { data: 'absent', name: 'absent' },
            { data: 'leave', name: 'leave' },
            { data: 'week_offs', name: 'week_offs' },
            { data: 'salary', name: 'salary' },
            { 
                data: 'final_salary', 
                name: 'final_salary',
                render: function (data) {
                    return '₹' + data;
                }
            },
            {
            data: 'salary_status',
            name: 'salary_status',
            render: function (data) {
               let badgeClass = (data === 'Paid') ? 'badge bg-success' : 'badge bg-danger';
                let icon = (data === 'Paid') ? 'check-circle' : 'exclamation-circle';
                return `<span class="${badgeClass}"><i class="fa fa-${icon}"></i> ${data}</span>`;
            }
            },
            { 
                data: 'staff_id',
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    let actionButton;
                
                if (row.salary_status === 'Paid') {
                    // Show View Slip (PDF Download icon)
                    actionButton = `<button class="btn btn-sm btn-primary" onclick="downloadSlip(${data}, ${month}, ${year})" title="Download Slip">
                                        View Salary slip
                                    </button>`;
                } else {
                    // Show Action (View/Pay Now)
                    actionButton = `<button class="btn btn-sm btn-primary" onclick="downloadPreviewSlipFn(${data}, ${month}, ${year})" title="View Projected Slip">
                                View PDF
                            </button> <button class="btn btn-sm btn-success" onclick="viewSlip(${data}, ${month}, ${year})" title="View/Pay Now">
                                        Pay Now
                                    </button>`;
                }
                
                return actionButton;
                }
            }
        ],
        language: {
            emptyTable: "No salary data available for selected filters"
        }
    });
}

 $('#salary_staff_id, #salary_year, #salary_month').select2({ width: '100%' });

function viewSlip(staff_id, month, year) {

    const slipUrl = '{{ route('salary.slip') }}';

   
    const staffSalaryUrl = '{{ route('salary.getStaffSalary') }}';

    $.get(slipUrl, { staff_id, month, year }, function(data) {
        // If salary is found
        let content = `
        <div class="modal-header">
          <h5 class="modal-title">Salary Slip - ${data.staff_name}</h5>
         
           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
          <table class="table table-borderless mb-0">
            <tbody>
                <tr>
                <th>Base Salary:</th><td>₹${data.base_salary}</td>
                <th>Deductions:</th><td>₹${data.deductions}</td>
                </tr>
                <tr>
                <th>Final Salary:</th><td>₹${data.final_salary}</td>
                <th>Present Days:</th><td>${data.present_days}</td>
                </tr>
                <tr>
                <th>Absent Days:</th><td>${data.absent_days}</td>
                <th>Leave Days:</th><td>${data.leave_days}</td>
                </tr>
                <tr>
                <th>Week Offs:</th><td>${data.week_offs}</td>
                <th>LWP Days:</th><td>${data.lwp_days}</td>
                </tr>
                <tr>
                <th>Half Days:</th><td colspan="3">${data.half_days}</td>
                </tr>
            </tbody>
            </table>

          <button class="btn btn-primary mt-2" onclick="downloadSlip(${staff_id}, ${month}, ${year})">Download PDF</button>
        </div>`;
        $('#salaryDetails').html(content);
        $('#salaryModal').modal('show');
    }).fail(() => {
        // If salary is not found, fetch staff info to show modal details
        $.get(staffSalaryUrl, { staff_id, month, year}, function(staff) {
            let content = `
            <div class="modal-header">
              <h5 class="modal-title">Salary Not Yet Paid - ${staff.staff_name}</h5>
              <!--<button type="button" class="close" data-dismiss="modal">&times;</button>-->
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                 <div class="row mb-3 pb-2 border-bottom">
                    <div class="col-6"><strong>Employee:</strong> ${staff.staff_name}</div>
                    <div class="col-6"><strong>Email:</strong> ${staff.staff_email}</div>
                    <div class="col-6"><strong>Department:</strong> ${staff.department}</div>
                    <div class="col-6"><strong>Designation:</strong> ${staff.position}</div>
                    <div class="col-6"><strong>Pay Month:</strong> ${staff.month}/${staff.year}</div>
                    <div class="col-6"><strong>Daily Salary:</strong> ₹${staff.daily_salary}</div>
                </div>

                 <h6 class="mt-3">Attendance Summary</h6>
        <table class="table table-sm table-bordered">
            <thead>
                <tr class="table-info">
                    <th>Total Working Days</th>
                    <th>Present Days</th>
                    <th>Absent Days</th>
                    <th>Leave Days</th>
                    <th>Week Offs</th>
                    <th>LWP Days</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>${staff.total_working_days}</td>
                    <td>${staff.present_days}</td>
                    <td>${staff.absent_days}</td>
                    <td>${staff.leave_days}</td>
                    <td>${staff.week_offs}</td>
                    <td>${staff.lwp_days}</td>
                </tr>
            </tbody>
        </table>

         <h6 class="mt-4">Projected Salary Calculation</h6>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-3">
                    <thead><tr class="table-success"><th>Earnings</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        <tr><td>Basic Salary</td><td class="text-end">₹${staff.base_salary}</td></tr>
                    </tbody>
                    <tfoot><tr class="table-success"><th>Total Earnings</th><th class="text-end">₹${staff.base_salary}</th></tr></tfoot>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-3">
                    <thead><tr class="table-danger"><th>Deductions</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        <tr><td>Absent/LWP Deduction</td><td class="text-end">₹${staff.deductions}</td></tr>
                    </tbody>
                    <tfoot><tr class="table-danger"><th>Total Deductions</th><th class="text-end">₹${staff.deductions}</th></tr></tfoot>
                </table>
            </div>
        </div>

         <h5 class="text-end mt-3 p-2 bg-light border">
            <strong>Projected Net Pay:</strong> ₹${staff.final_salary}
        </h5>

        <hr class="my-3">
        <p class="text-muted text-center">
            This is a preview based on current attendance data.
        </p>

            <br>
                <hr>
              <p>Salary not yet paid. You can now process it.</p>
              <button class="btn btn-success mt-2 float-end" onclick="confirmPay(${staff_id}, ${month}, ${year})">Pay Now</button>
            </div>`;
            $('#salaryDetails').html(content);
            $('#salaryModal').modal('show');
        });
    });
}



  function downloadSlip(staff_id, month, year) {
    window.open(`{{ route('salary.slip.download') }}?staff_id=${staff_id}&month=${month}&year=${year}`, '_blank');
  }
  function downloadPreviewSlipFn(staff_id, month, year) {
    // This calls the new route we created for the on-the-fly PDF generation
    window.open(`{{ route('salary.slip.preview.download') }}?staff_id=${staff_id}&month=${month}&year=${year}`, '_blank');
}

function confirmPay(staff_id, month, year) {
    $.post('{{ route("salary.calculate") }}', {
        staff_id: staff_id,
        month: month,
        year: year,
        _token: '{{ csrf_token() }}'
    }, function(res) {
        alert('Salary calculated and paid.');
        $('#salaryModal').modal('hide');
        loadSalaryData(); // Refresh
    }).fail(() => {
        alert('Error processing salary.');
    });
}

</script>


</body>

</html>
