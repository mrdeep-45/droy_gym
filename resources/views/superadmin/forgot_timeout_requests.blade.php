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
                                                
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#company-list"
                                                            aria-selected="true">Forgot timeout Request List</a>
                                                    </li>
                                               

                                                
                                                    <li class="nav-item ">
                                                       
                                                            <!-- <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" id="companyTabLabel1" href="#new-unit" aria-selected="false">New Unit</a>-->
                                                    </li>
                                                    
                                               
                                            </ul>
                                            
                                            <div class="tab-content">
                                               
                                                    <div class="tab-pane border-0 show active text-muted px-1 "
                                                        id="company-list" role="tabpanel">
                                                        <div class="row">
                                                           
                                                           <br>
                                                           

                                                            <table id="rawdata"
                                                                class="table table-bordered  menu-submenu-data"
                                                                style="width:100%">
                                                                 <thead>
                                                                <tr>
                                                                    <th style="width:1%;">#</th>
                                                                    <th style="width:8%;">Name</th>
                                                                    <!--<th style="width:3%">Status</th>-->
                                                                    <th style="width:4%;">Date</th>
                                                                    <th style="width:8%;">Description</th>
                                                                    <th class="text-center" style="width:8%;">Action</th>
                                                                </tr>
                                                            </thead>
                                                                <tbody>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                               <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Handle Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><strong>Name:</strong> <span id="modalName"></span></p>
        <p><strong>Date:</strong> <span id="modalDate"></span></p>
        <p><strong>Description:</strong> <span id="modalDesc"></span></p>
        <div id="requestHistoryContainer" class="mt-3">
  <strong>Request History:</strong>
  <ul id="requestHistoryList" class="list-unstyled mb-0"></ul>
</div>


        <div id="approveForm" style="display: none;">
          <label>Time Out</label>
          <input type="time" class="form-control mb-2" id="modalTimeOut">
          <!--<label>Approve Remark</label>-->
          <!--<input type="text" class="form-control mb-2" id="modalApproveRemark">-->
          <button class="btn btn-success" id="modalApproveBtn">Approve</button>
        </div>

        <div id="rejectForm" style="display: none;">
          <label>Rejection Reason</label>
          <input type="text" class="form-control mb-2" id="modalRejectRemark">
          <button class="btn btn-danger" id="modalRejectBtn">Reject</button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-success" id="showApproveForm">Approve</button>
        <button class="btn btn-outline-danger" id="showRejectForm">Reject</button>
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
        </div>
<!--</div>-->

        @include('company/delete_modal')
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')

    <script>

          
        $(document).ready(function() {

          var forgetTable = $('#rawdata').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('ForgetTimeoutList') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'staff_name', name: 'staff_name' },
            { data: 'date', name: 'date' },
            { data: 'description', name: 'description' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ],
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>',
            emptyTable: 'No requests found',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)'
        }
    });

  

        });

       
       
/*
         // Focus on input when new country tab is shown
            $('a[href="#new-unit"]').on('shown.bs.tab', function () {
                setTimeout(function () {
                    $('#unit').trigger('focus');
                }, 200);
            });
            

            // Reset form on tab switch
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr('href');
            if (target === '#new-unit') {
                if (!editMode) setFormToCreateMode();
            }
            if (target === '#company-list') {
                setFormToCreateMode();
            }
        });
*/
       



    let currentRequestId = null;

$('#statusModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const name = button.data('name');
    const date = button.data('date');
    const desc = button.data('description');
     const history = button.data('history'); // JSON array
    currentRequestId = button.data('id');

    $('#modalName').text(name);
    $('#modalDate').text(date);
    $('#modalDesc').text(desc);

    $('#approveForm').hide();
    $('#rejectForm').hide();

    // Clear previous history
    const $historyList = $('#requestHistoryList');
    $historyList.empty();

    if (Array.isArray(history) && history.length > 0) {
        history.forEach(item => {
            const badgeClass = item.status === 'approved' ? 'success' : 'danger';
            $historyList.append(`
                <li class="mb-2">
                    <span class="badge bg-${badgeClass}">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span>
                    - ${item.remark ?? 'No remark'}<br>
                    <small class="text-muted">${item.created_at}</small>
                </li>
            `);
        });
        $('#requestHistoryContainer').show();
    } else {
        $('#requestHistoryContainer').hide();
    }
});

// Show approve section
$('#showApproveForm').on('click', function () {
    $('#rejectForm').hide();
    $('#approveForm').show();
});

// Show reject section
$('#showRejectForm').on('click', function () {
    $('#approveForm').hide();
    $('#rejectForm').show();
});

// Approve action
$('#modalApproveBtn').on('click', function () {
    const timeOut = $('#modalTimeOut').val();
    const remark = $('#modalApproveRemark').val();

    if (!timeOut) {
        iziToast.warning({ title: 'Warning', message: 'Please select a time.', position: 'topRight' });
        return;
    }

    $.ajax({
        url: "{{ route('superadmin.forgot_timeout.update') }}",
        method: "POST",
        data: {
            _token: '{{ csrf_token() }}',
            request_id: currentRequestId,
            time_out: timeOut,
            remark: remark
        },
        success: function (res) {
            iziToast.success({ title: 'Approved', message: res.message, position: 'topRight' });
            $('#statusModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function (xhr) {
            iziToast.error({ title: 'Error', message: xhr.responseJSON?.message || 'Something went wrong.', position: 'topRight' });
        }
    });
});

// Reject action
$('#modalRejectBtn').on('click', function () {
    const remark = $('#modalRejectRemark').val();

    if (!remark) {
        iziToast.warning({ title: 'Warning', message: 'Please enter a reason.', position: 'topRight' });
        return;
    }

    $.ajax({
        url: "{{ route('superadmin.forgot_timeout.reject') }}",
        method: "POST",
        data: {
            _token: '{{ csrf_token() }}',
            request_id: currentRequestId,
            remark: remark
        },
        success: function (res) {
            iziToast.info({ title: 'Rejected', message: res.message, position: 'topRight' });
            $('#statusModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        },
        error: function (xhr) {
            iziToast.error({ title: 'Error', message: xhr.responseJSON?.message || 'Something went wrong.', position: 'topRight' });
        }
    });
});

    </script>

</body>

</html>
