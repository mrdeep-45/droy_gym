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
                                            <a class="nav-link active" data-bs-toggle="tab" href="#dietplan-list">List</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#new-dietplan" id="companyTabLabel">New Plan</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane border-0 show active text-muted px-1" id="dietplan-list" role="tabpanel">
                                            <table id="dietplanData" class="table table-bordered menu-submenu-data" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Member</th>
                                                        <th>Trainer</th>
                                                        <th>Start</th>
                                                        <th>End</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="tab-pane text-muted border-0 px-1" id="new-dietplan" role="tabpanel">
                                            <form id="dietplan_form" novalidate>
                                                @csrf
                                                <input type="hidden" name="plan_record_id" id="plan_record_id">
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Member <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="member_id" id="member_id">
                                                            <option value="">Select Member</option>
                                                            @foreach($members as $m)
                                                                <option value="{{ $m->id }}">{{ $m->full_name }} ({{ $m->member_number }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Assigned By Trainer <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="trainer_id" id="trainer_id">
                                                            <option value="">Select Trainer</option>
                                                            @foreach($trainers as $t)
                                                                <option value="{{ $t->trainer_id }}">{{ $t->trainer_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" name="start_date" id="start_date">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" name="end_date" id="end_date">
                                                    </div>
                                                </div>

                                                <h6 class="mt-4 mb-2"><i class="fas fa-dumbbell"></i> Weekly Workout Schedule</h6>
                                                <div class="row">
                                                    @foreach($days as $day)
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">{{ $day }}</label>
                                                            <textarea class="form-control workout-field" name="workout_{{ $day }}" rows="2"
                                                                placeholder="e.g., Chest + Triceps"></textarea>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <h6 class="mt-4 mb-2"><i class="fas fa-utensils"></i> Daily Diet Chart</h6>
                                                <div class="row">
                                                    @foreach($meals as $meal)
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">{{ $meal }}</label>
                                                            <textarea class="form-control diet-field" name="diet_{{ str_replace('-', '_', $meal) }}" rows="2"
                                                                placeholder="e.g., Oats + 3 egg whites"></textarea>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                                                </div>
                                            </form>
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
        const days = @json($days);
        const meals = @json($meals);

        function setFormToCreateMode() {
            editMode = false;
            $('#companyTabLabel').text('New Plan');
            $('#submitBtn').text('Submit');
            $('#dietplan_form')[0].reset();
            $('#plan_record_id').val('');
        }

        $(document).ready(function() {
            var table = $('#dietplanData').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('dietplan.list') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, title: '#' },
                    { data: 'member_name', name: 'member_name', title: 'Member' },
                    { data: 'trainer_name', name: 'trainer_name', title: 'Trainer' },
                    { data: 'start_date', name: 'start_date', title: 'Start' },
                    { data: 'end_date', name: 'end_date', title: 'End' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions' }
                ],
                responsive: true
            });

            $(document).on('click', '.edit-dietplan', function() {
                const id = $(this).data('id');
                $.get("{{ route('dietplan.edit', ':id') }}".replace(':id', id), function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        editMode = true;
                        $('#plan_record_id').val(data.id);
                        $('#member_id').val(data.member_id);
                        $('#trainer_id').val(data.trainer_id);
                        $('#start_date').val(data.start_date);
                        $('#end_date').val(data.end_date);

                        const workout = data.workout_details || {};
                        days.forEach(day => {
                            $(`[name="workout_${day}"]`).val(workout[day] || '');
                        });

                        const diet = data.diet_details || {};
                        meals.forEach(meal => {
                            const fieldName = meal.replace('-', '_');
                            $(`[name="diet_${fieldName}"]`).val(diet[meal] || '');
                        });

                        $('#companyTabLabel').text('Edit Plan');
                        $('#submitBtn').text('Update');
                        $('a[href="#new-dietplan"]').tab('show');
                    }
                });
            });

            $(document).on('click', '.delete-dietplan', function() {
                const id = $(this).data('id');
                iziToast.question({
                    timeout: 20000, close: false, overlay: true, displayMode: 'once',
                    id: 'question', zindex: 999, title: 'Confirm',
                    message: 'Delete this diet/workout plan?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function(instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            $.ajax({
                                url: "{{ route('dietplan.destroy') }}",
                                type: "POST",
                                data: { _token: "{{ csrf_token() }}", plan_record_id: id },
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

            $('#dietplan_form').submit(function(e) {
                e.preventDefault();
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('dietplan.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        iziToast.success({
                            title: 'Success', message: response.message, position: 'topRight', timeout: 700,
                            onClosed: function() {
                                table.ajax.reload();
                                $('a[href="#dietplan-list"]').tab('show');
                                setFormToCreateMode();
                            }
                        });
                    },
                    error: function(xhr) {
                        iziToast.error({ title: 'Error', message: xhr.responseJSON?.message || 'Something went wrong', position: 'topRight' });
                    },
                    complete: function() {
                        $('#submitBtn').prop('disabled', false).html(editMode ? 'Update' : 'Submit');
                    }
                });
            });
        });
    </script>
</body>

</html>
