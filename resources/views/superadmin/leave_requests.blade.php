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
                                                @if (permissions_check('canView'))
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#company-list"
                                                            aria-selected="true">Leave Request List</a>
                                                    </li>
                                                @endif

                                                @if (permissions_check('canCreate'))
                                                    <li class="nav-item ">
                                                       
                                                            <!-- <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" id="companyTabLabel1" href="#new-unit" aria-selected="false">New Unit</a>-->
                                                    </li>
                                                @endif
                                            </ul>
                                            <div class="tab-content">
                                                @if (permissions_check('canView'))
                                                    <div class="tab-pane border-0 show active text-muted px-1 "
                                                        id="company-list" role="tabpanel">
                                                        <div class="row">
                                                            <table id="rawdata"
                                                                class="table table-bordered  menu-submenu-data"
                                                                style="width:100%">
                                                                <thead>
                                                                <tr>
                                                                    <th style="width:1%;">#</th>
                                                                    <th style="width:6%;">Name</th>
                                                                    <th style="width:6%;">Leave Duration</th>
                                                                    <th style="width:3%;">Total Days</th>
                                                                    <th style="width:4%;">Leave type</th>
                                                                    
                                                                    <th style="width:4%;">Leave Reason</th>
                                                                    <th style="width:2%;">status</th>
                                                                    <th class="text-center" style="width:2%;">Action</th>
                                                                </tr>
                                                            </thead>
                                                                <tbody>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                              <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-center w-100">Handle Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <table class="table table-borderless mb-0">
  <tbody>
    <tr>
      <th>Staff Name:</th><td id="modalName"></td>
      <th>Date:</th><td id="modalDate"></td>
    </tr>
    <tr>
      <th>Leave Type:</th><td id="modalType"></td>
      <th>Total Days:</th><td id="modalDays"></td>
    </tr>
    <tr>
      <th>Available Leave:</th><td id="modalBalance"></td>
      <th>Notes:</th><td id="modalNotes"></td>
    </tr>
    <tr>
      <th>Leave Reason:</th><td colspan="3" id="modalDesc"></td>
    </tr>
  </tbody>
</table>
<style>
    .modal-header {
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
}
.modal-title {
  font-weight: bold;
  color: #333;
}

</style>

        <!-- Request history -->
        <div id="requestHistoryContainer" class="mt-3">
          <strong>Request History:</strong>
          <ul id="requestHistoryList" class="list-group list-group-flush mt-2"></ul>
        </div>

        <!-- Reject form only -->
        <div id="rejectForm" style="display: none;">
          <label><strong>Rejection Reason</strong></label>
          <input type="text" class="form-control mb-2" id="modalRejectRemark" placeholder="Enter reason for rejection">
          <button class="btn btn-danger float-end" id="modalRejectBtn">❌ Reject</button>
        </div>
      </div>

      <div class="modal-footer">
        <div class="d-flex justify-content-center gap-3">
            <button class="btn btn-outline-success" id="modalApproveBtn">✅ Approve</button>
            <button class="btn btn-outline-danger" id="showRejectForm">❌ Reject</button>
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
        </div>
<!--</div>-->

        @include('company/delete_modal')
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')

    <script>

          
        $(document).ready(function() {

         var leaveTable = $('#rawdata').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('superadmin.leave.list') }}",
    columns: [
         { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'staff_name', name: 'staff_name' },
        { data: 'duration', name: 'leave_duration' },
         { data: 'total_days', name: 'total_days' },
        { data: 'leave_type', name: 'leave_type' },
        { data: 'reason', name: 'reason' },
        { data: 'status', name: 'status' },
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
        emptyTable: 'No Leave allocation found',
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

    currentRequestId = button.data('id'); // ensure ID is captured
    $('#modalName').text(button.data('name'));
    $('#modalDate').text(button.data('duration'));
    $('#modalDesc').text(button.data('reason'));
    $('#modalType').text(button.data('type'));
    $('#modalDays').text(button.data('total-days'));
    $('#modalBalance').text(button.data('balance'));
    $('#modalNotes').text(button.data('notes'));

    const status = button.data('status');
    const processed = button.data('processed') === 'yes';

    // Hide forms
    $('#rejectForm').hide();

    // Hide action buttons if already processed
    if (processed) {
        $('#modalApproveBtn').hide();
        $('#showRejectForm').hide();
    } else {
        $('#modalApproveBtn').show();
        $('#showRejectForm').show();
    }

    // Populate request history
    const history = button.data('history');
    const $historyList = $('#requestHistoryList');
    $historyList.empty();

    if (Array.isArray(history) && history.length > 0) {
        history.forEach(item => {
            const badgeClass = item.status === 'approved' ? 'success' : 'danger';
            $historyList.append(`
                <li class="list-group-item">
                    <span class="badge bg-${badgeClass} me-2">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span>
                    - ${item.remark ?? 'No remark'}<br>
                    <small class="text-muted small">${item.created_at}</small>
                </li>
            `);
        });
        $('#requestHistoryContainer').show();
    } else {
        $('#requestHistoryContainer').hide();
    }
});

// Show rejection reason input
$('#showRejectForm').on('click', function () {
    $('#rejectForm').show();
});

// Approve action
$('#modalApproveBtn').on('click', function () {
    $.ajax({
        url: "{{ route('leave.approve') }}",
        method: "POST",
        data: {
            _token: '{{ csrf_token() }}',
            leavemid: currentRequestId,
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
        url: "{{ route('leave.reject') }}",
        method: "POST",
        data: {
            _token: '{{ csrf_token() }}',
            leavemid: currentRequestId,
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
