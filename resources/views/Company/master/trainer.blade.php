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
                                                            aria-current="page" href="#trainer-list"
                                                            aria-selected="true">List</a>
                                                    </li>
                                                @endif

                                                @if (permissions_check('canCreate'))
                                                    <li class="nav-item ">
                                                        <a class="nav-link" data-bs-toggle="tab" role="tab"
                                                            aria-current="page" href="#new-trainer"
                                                            aria-selected="false" id="companyTabLabel">New Trainer</a>
                                                    </li>
                                                @endif
                                            </ul>
                                            <div class="tab-content">
                                                @if (permissions_check('canView'))
                                                    <div class="tab-pane border-0 show active text-muted px-1 "
                                                        id="trainer-list" role="tabpanel">
                                                        <div class="row">
                                                            <table id="trainerData"
                                                                class="table table-bordered  menu-submenu-data"
                                                                style="width:100%">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:1%;">#</th>

                                                                        <th>Photo</th>
                                                                        <th>Name</th>
                                                                        <th>Phone</th>
                                                                        <th>Specialization</th>

                                                                        <th class="text-center">Action </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (permissions_check('canCreate'))
                                                    <div class="tab-pane text-muted border-0 px-1 " id="new-trainer"
                                                        role="tabpanel">
                                                        {{-- <div class="d-flex justify-content-end">
                                                        <small class="text-danger me-2">* Fields are mandatory</small>
                                                    </div> --}}
                                                       
                                                        <form id="trainer_form" enctype="multipart/form-data"
                                                            novalidate>
                                                            @csrf
                                                            <input type="hidden" name="trainer_id" id="trainer_id">
                                                           <div class="row">
                                                           <div class="col-md-6 mb-3">
                                                                <label class="form-label">Trainer Name <span class="text-danger">*</span></label>

                                                                <input type="text"
                                                                    class="form-control"
                                                                    name="trainer_name"
                                                                    id="trainer_name">

                                                                <div class="invalid-feedback"></div>
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Phone <span class="text-danger">*</span></label>

                                                                <input type="text"
                                                                    class="form-control"
                                                                    name="trainer_phone"
                                                                    id="trainer_phone">

                                                                <div class="invalid-feedback"></div>
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Specialization <span class="text-danger">*</span></label>

                                                                <input type="text"
                                                                    class="form-control"
                                                                    name="specialization"
                                                                    id="specialization">

                                                                <div class="invalid-feedback"></div>
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label>Photo</label>

                                                                <input type="file"
                                                                    class="form-control"
                                                                    name="t_photo"
                                                                    id="t_photo">

                                                                <img
                                                                    id="previewPhoto"
                                                                    width="80"
                                                                    class="mt-2 d-none">
                                                            </div>
                                                           </div>
                                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                                <button type="submit" class="btn btn-primary"
                                                                    id="submitBtn">Submit</button>
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
            $('#trainer_form').removeAttr('data-mode');
            $('#companyTabLabel').text('New Trainer');
            $('#submitBtn').text('Submit');
            $('input[name="plan_id[]"]').remove();
            // Clear input fields
            $('.group-name-row:not(:first)').remove();
            $('.group-name-row:first input').val('');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        function setFormToEditMode(data) {
            editMode = true;
            $('#trainer_form').attr('data-mode', 'edit');
            $('#companyTabLabel').text('Edit Trainer');
            $('#submitBtn').text('Update');
            // Remove any old hidden fields then add new one
            $('input[name="plan_id[]"]').remove();
    $('#trainer_form').append(
        `<input type="hidden" name="plan_id[]" value="${data.plan_id}">`
    );
            // Only single value
            $('input[name="plan_name[]"]').first().val(data.plan_name);
    $('input[name="duration[]"]').first().val(data.duration);
    $('input[name="price[]"]').first().val(data.price);
    $('textarea[name="description[]"]').first().val(data.description);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').hide();
        }

        $(document).ready(function() {
            // DataTable Init
             var canUpdate = {{ permissions_check('canUpdate') ? 'true' : 'false' }};
            var canDelete = {{ permissions_check('canDelete') ? 'true' : 'false' }};
            var table = $('#trainerData').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('trainer.list') }}",
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false,
                title: '#'
            },
            {
                data: 'photo',
                name: 'photo',
                title: 'photo'
            },
            {
                data: 'trainer_name',
                name: 'trainer_name',
                title: 'Trainer Name'
            },
            {
                data: 'trainer_phone',
                name: 'trainer_phone',
                title: 'Trainer Phone'
            },
            {
                data: 'specialization',
                name: 'specialization',
                title: 'Specialization'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                title: 'Actions',
                 visible: canUpdate || canDelete
            }
        ],
        responsive: true
    });

            $('#refreshBtn').click(() => table.ajax.reload());

        

            // Tab show event: update tab label etc on display
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');
                if (target === '#new-trainer') {
                    if (editMode) {
                        $('#companyTabLabel').text('Edit Trainer');
                        $('#submitBtn').text('Update');
                    } else {
                        setFormToCreateMode(); // will also update label etc.
                    }
                }
                // If switching to list, always reset form
                if (target === '#trainer-list') {
                    setFormToCreateMode();
                }
            });

            // Handle edit button click
            $(document).on(
    'click',
    '.edit-trainer',
    function(){

        let id = $(this).data('id');

        $.get(
            "{{ route('trainer.edit',':id') }}"
            .replace(':id',id),

            function(response){

                let data = response.data;

                $('#trainer_id').val(data.trainer_id);

                $('#trainer_name')
                    .val(data.trainer_name);

                $('#trainer_phone')
                    .val(data.trainer_phone);

                $('#specialization')
                    .val(data.specialization);

                if(data.t_photo)
                {
                    $('#previewPhoto')
                        .removeClass('d-none')
                        .attr(
                            'src',
                            '/uploads/trainers/'+
                            data.t_photo
                        );
                }
            }
        );
});

            // Handle delete
            $(document).on(
    'click',
    '.delete-trainer',
    function(){

        let id = $(this).data('id');

        $.ajax({

            url:"{{ route('trainer.destroy') }}",

            type:"POST",

            data:{
                _token:"{{ csrf_token() }}",
                trainer_id:id
            },

            success:function(response){

                iziToast.success({
                    title:'Success',
                    message:response.message
                });

                table.ajax.reload();
            }
        });
});

            // Handle form submission (add and edit)
            $('#trainer_form').submit(function(e){

e.preventDefault();

let formData = new FormData(this);

$.ajax({

    url:"{{ route('trainer.store') }}",

    type:"POST",

    data:formData,

    processData:false,
    contentType:false,

    success:function(response){

        iziToast.success({
            title:'Success',
            message:response.message
        });

        $('#trainer_form')[0].reset();

        $('#trainer_id').val('');

        table.ajax.reload();
    }
});
});

            // Reset form handler: set back to create mode
            $('#trainer_form').on('reset', function() {
                setFormToCreateMode();
            });

            // Real-time validation for plan_name fields (all, since dynamic)
            $(document).on('input', 'input[name="plan_name[]"]', function() {
                if ($(this).val().trim()) {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').hide();
                }
            });
        });
    </script>

</body>

</html>
