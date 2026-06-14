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
                                                            aria-selected="true">Leave Allocation List</a>
                                                    </li>
                                               

                                                
                                                    <li class="nav-item ">
                                                       
                                                            <!-- <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" id="companyTabLabel1" href="#new-unit" aria-selected="false">New Unit</a>-->
                                                    </li>
                                                    <div class="col-md-3">
                                                                
                                                          @php
    $currentYear = date('Y');
@endphp

<select id="yearFilter" class="form-control">
    @for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
        <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
    @endfor
</select>


                                                            </div>
                                               
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
                                                                    <th style="width:8%;"> Staff Name</th>
                                                                    <th style="width:3%">CL</th>
                                                                    <th style="width:3%;">PL</th>
                                                                    <th style="width:3%;">SL</th>
                                                                    <th style="width:3%;">LWP</th>
                                                                    <th style="width:3%;">year</th>
                                                                    <th class="text-center" style="width:3%;">Action</th>
                                                                </tr>
                                                            </thead>
                                                                <tbody>
                                                                </tbody>
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
            var inputCache = {};
          var leaveTable = $('#rawdata').DataTable({
    processing: true,
    serverSide: true,
     ajax: {
            url: "{{ route('superadmin.leavebalance.list') }}",
            data: function(d) {
                d.year = $('#yearFilter').val();
            }
        },
    columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-left' },
        { data: 'staff_name', name: 'staff.name' },
      { data: 'cl', name: 'cl' },
      { data: 'pl', name: 'pl' },
      { data: 'sl', name: 'sl' },
      { data: 'lwp', name: 'lwp' },
      { data: 'year', name: 'year' },
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
    },
   // Add this to cache and restore input fields
    drawCallback: function (settings) {
        $('#rawdata tbody tr').each(function () {
            var row = $(this);
            var leavebmid = row.find('input').first().data('id');

            if (!inputCache[leavebmid]) {
                inputCache[leavebmid] = {
                    cl: row.find('.cl-input').val(),
                    pl: row.find('.pl-input').val(),
                    sl: row.find('.sl-input').val(),
                    lwp: row.find('.lwp-input').val()
                };
            } else {
                // Reapply cached input values
                row.find('.cl-input').val(inputCache[leavebmid].cl);
                row.find('.pl-input').val(inputCache[leavebmid].pl);
                row.find('.sl-input').val(inputCache[leavebmid].sl);
                row.find('.lwp-input').val(inputCache[leavebmid].lwp);
            }
        });
    }

});

  $('.select2').select2({
                ajax: {
                    delay: 250
                    , cache: true
                }
            });
            $('#yearFilter').select2({
                width: '100%'
            });
// Refresh on year change
    $('#yearFilter').on('change', function() {
        leaveTable.ajax.reload();
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
       


/*
     $(document).on('change', '.cl-input, .sl-input, .pl-input, .lwp-input', function () {
    var input = $(this);
    var value = input.val();
    var leavebmid = input.data('id');
    var type = 'CL';
    // Mark the row as edited
    input.closest('tr').addClass('edited-row');

    if (input.hasClass('sl-input')) {
        type = 'SL';
    } else if (input.hasClass('pl-input')) {
        type = 'PL';
    }
    else if (input.hasClass('lwp-input')) {
        type = 'LWP';
    }

    $.ajax({
        url: "{{ route('superadmin.leavebalance.update') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            leavebmid: leavebmid,
            type: type,
            value: value
        },
       success: function (response) {
    if (response.success) {
        iziToast.success({
            title: 'Success',
            message: response.message,
            position: 'topRight',
            timeout: 3000
        });

        // Get updated values
        var data = response.data;

        // Find the row
        var row = input.closest('tr');

        // Get the employee name cell
        var nameCell = row.find('td:eq(1)'); // 2nd cell is name column

        // Get only the name without previous leave data
        var fullText = nameCell.text().trim();

        // Use regex to remove previous CL/SL/PL/LWP text if exists
        var cleanedName = fullText.replace(/CL-\d+\s*SL-\d+\s*PL-\d+\s*LWP-\d+/gi, '').trim();

        // Build new leave summary
        var newSummary = `CL-${data.CL} SL-${data.SL} PL-${data.PL} LWP-${data.LWP}`;

        // Update the cell content
        nameCell.html(`${cleanedName}<br><small>${newSummary}</small>`);

    } else {
        iziToast.warning({
            title: 'Warning',
            message: 'Failed to update leave balance',
            position: 'topRight'
        });
    }
},
error: function(xhr) {
    iziToast.error({
        title: 'Error',
        message: xhr.responseJSON?.message || 'Something went wrong.',
        position: 'topRight'
    });
}

    });
});
*/

//  Event: Input field value edited
$(document).on('input', '.cl-input, .pl-input, .sl-input, .lwp-input', function () {
    var input = $(this);
    input.closest('tr').addClass('edited-row');
});
//event : admin click edit button 
$(document).on('click', '.edit', function (e) {
    var row = $(this).closest('tr');
    var leavebmid = $(this).data('id');

    if (!row.hasClass('edited-row')) {
        iziToast.warning({
            title: 'Warning',
            message: 'Please first edit the leave balance',
            position: 'topRight'
        });
        return;
    }

    // Get new input values
    var cl = row.find('.cl-input').val();
    var pl = row.find('.pl-input').val();
    var sl = row.find('.sl-input').val();
    var lwp = row.find('.lwp-input').val();

    // Send updated values to backend
    $.ajax({
        url: "{{ route('superadmin.leavebalance.update') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            leavebmid: leavebmid,
            cl: cl,
            pl: pl,
            sl: sl,
            lwp: lwp
        },
        success: function (response) {
            if (response.success) {
                iziToast.success({
                    title: 'Success',
                    message: response.message,
                    position: 'topRight',
                    timeout: 3000
                });

                // Update credit summary only
                var data = response.data;
                var nameCell = row.find('td:eq(1)');
                var fullText = nameCell.text().trim();
                var cleanedName = fullText.replace(/CL-\d+\s*PL-\d+\s*SL-\d+\s*LWP-\d+/gi, '').trim();
               // var newSummary = `CL-${data.CL} PL-${data.PL} SL-${data.SL} LWP-${data.LWP}`;
               var newSummary = `CL-${Math.max(0, data.CL)} PL-${Math.max(0, data.PL)} SL-${Math.max(0, data.SL)} LWP-${Math.max(0, data.LWP)}`;

                nameCell.html(`${cleanedName}<br><small>credit:${newSummary}</small>`);

                // Update inputCache so values are preserved after reload
                inputCache[leavebmid] = { cl, pl, sl, lwp };

                // Remove edited-row class
                row.removeClass('edited-row');
            } else {
                iziToast.warning({
                    title: 'Warning',
                    message: 'Failed to update leave balance',
                    position: 'topRight'
                });
            }
        },
        error: function (xhr) {
            iziToast.error({
                title: 'Error',
                message: xhr.responseJSON?.message || 'Something went wrong.',
                position: 'topRight'
            });
        }
    });
    });
    </script>

</body>

</html>
