<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient"
    data-menu-styles="light">

<head>

    @include('include/meta_tags')
    @include('include/header_links')
    @include('include/datatable_css_link')

    <style>
        /* Custom styling for square checkboxes */
        .square-checkbox {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.15em;
            border: 2px solid #adb5bd;
            border-radius: 4px;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .square-checkbox:checked {
            background-color: #6259ca;
            border-color: #6259ca;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
        }

        .square-checkbox:focus {
            box-shadow: 0 0 0 0.25rem rgba(98, 89, 202, 0.25);
            outline: none;
        }

        .square-checkbox:hover {
            border-color: #6259ca;
        }



        .border-top {
            border-top: 1px solid #f0f0ff !important;
        }

        /* Form select styling */
        .form-select {
            border: 1px solid #e8e8f7;
            border-radius: 4px;
            padding: 0.5rem 1rem;
        }

        .form-select:focus {
            border-color: #6259ca;
            box-shadow: 0 0 0 0.25rem rgba(98, 89, 202, 0.25);
        }
    </style>
</head>

<body>
    @include('include/switcher')
    @include('include/loader')
    <div class="page">
        @include('include/top')
        @include('include/left')
        <div class="page-header-breadcrumb d-md-flex d-block align-items-center justify-content-between "></div>
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xxl-12 col-xl-12">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <ul class="nav nav-pills justify-content-left nav-style-2 mb-1" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                                    aria-current="page" href="#menu-list" aria-selected="true">List</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" role="tab"
                                                    aria-current="page" href="#new-menu" aria-selected="false">New
                                                    Permission</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" role="tab"
                                                    aria-current="page" href="#dashboard-permission"
                                                    aria-selected="false">Dashbard
                                                    Permission</a>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane border-0 show active text-muted px-1" id="menu-list"
                                                role="tabpanel">
                                                <div class="row">
                                                    <table id="datatable-basic"
                                                        class="table table-bordered  menu-submenu-data"
                                                        style="width:100%">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:1%;">#</th>
                                                                <th style="width: 20%">Role Name</th>
                                                                <th style="width:20%">Menu</th>
                                                                <th style="width:49%">Submenu</th>
                                                                <th class="text-center" style="width:10%">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane text-muted border-0 px-1" id="new-menu"
                                                role="tabpanel">
                                                <form id="PermissionForm" method="POST" action=""
                                                    class="needs-validation" novalidate>
                                                    @csrf


                                                    <input type="hidden" name="is_edit" id="is_edit" value="0">
                                                    <input type="hidden" name="role_id" id="form_role_id"
                                                        value="">
                                                    <input type="hidden" name="edit_role_id" id="edit_role_id"
                                                        value="">

                                                    <div class="row mb-4">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Role<span
                                                                    class="text-danger">*</span></label>
                                                            <select
                                                                class="form-control js-example-placeholder-single js-states"
                                                                name="role_id" id="role_id" required>
                                                                <option value="" selected disabled>Select Role
                                                                </option>
                                                                @foreach ($roles as $role)
                                                                    <option value="{{ $role->role_id }}">
                                                                        {{ $role->role_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="invalid-feedback">Role is required</div>
                                                        </div>
                                                    </div>

                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered table-hover">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th width="5%">#</th>
                                                                            <th width="25%">Menu</th>
                                                                            <th width="40%">Permissions</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @php $count = 1; @endphp
                                                                        @foreach ($menuList as $menuId => $menu)
                                                                            <tr>
                                                                                <td class="align-middle">
                                                                                    {{ $count++ }}</td>
                                                                                <td class="align-middle">
                                                                                    <strong>{{ $menu['menu_title'] }}</strong>
                                                                                    <input type="hidden"
                                                                                        name="menu_id[]"
                                                                                        value="{{ $menuId }}">
                                                                                </td>
                                                                                <td class="align-middle">
                                                                                    @if (!empty($menu['submenus']))
                                                                                        @foreach ($menu['submenus'] as $submenu)
                                                                                            <div class="mb-2">
                                                                                                <strong
                                                                                                    class="d-block mb-1 text-danger">{{ $submenu['sub_menu_title'] }}</strong>
                                                                                                <div
                                                                                                    class="d-flex flex-wrap gap-2">
                                                                                                    @foreach (['view', 'create', 'update', 'delete'] as $label)
                                                                                                        <div
                                                                                                            class="form-check form-check-inline">
                                                                                                            <input
                                                                                                                type="hidden"
                                                                                                                name="submenu_{{ $label }}[{{ $menuId }}][{{ $submenu['sub_menu_id'] }}]"
                                                                                                                value="0">
                                                                                                            <input
                                                                                                                type="checkbox"
                                                                                                                class="form-check-input"
                                                                                                                name="submenu_{{ $label }}[{{ $menuId }}][{{ $submenu['sub_menu_id'] }}]"
                                                                                                                value="1"
                                                                                                                id="submenu_{{ $submenu['sub_menu_id'] }}_{{ $label }}">
                                                                                                            <label
                                                                                                                class="form-check-label"
                                                                                                                for="submenu_{{ $submenu['sub_menu_id'] }}_{{ $label }}">{{ ucfirst($label) }}</label>
                                                                                                        </div>
                                                                                                    @endforeach
                                                                                                </div>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @else
                                                                                        <div
                                                                                            class="d-flex flex-wrap gap-2">
                                                                                            @foreach (['view', 'create', 'update', 'delete'] as $label)
                                                                                                <div
                                                                                                    class="form-check form-check-inline">
                                                                                                    <input
                                                                                                        type="hidden"
                                                                                                        name="menu_{{ $label }}[{{ $menuId }}]"
                                                                                                        value="0">
                                                                                                    <input
                                                                                                        type="checkbox"
                                                                                                        class="form-check-input"
                                                                                                        name="menu_{{ $label }}[{{ $menuId }}]"
                                                                                                        value="1"
                                                                                                        id="menu_{{ $menuId }}_{{ $label }}">
                                                                                                    <label
                                                                                                        class="form-check-label"
                                                                                                        for="menu_{{ $menuId }}_{{ $label }}">{{ ucfirst($label) }}</label>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>

                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-12 text-end">
                                                            <button type="submit" class="btn btn-primary px-4">
                                                                <i class="fas fa-save me-2"></i> Save Permissions
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>



                                            </div>





                                            <div class="tab-pane text-muted border-0 px-1" id="dashboard-permission">
                                                <!-- Role Selection Dropdown -->
                                                <div class="row mb-4">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Select Role</label>
                                                            <select class="form-control form-select" id="role-select"
                                                                name="role_id">
                                                                <option value="" disabled selected>Select Role
                                                                </option>
                                                                @foreach ($roles as $role)
                                                                    <option value="{{ $role->role_id }}">
                                                                        {{ $role->role_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Permission Form -->
                                                <form id="permission-form" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="role_id" id="form-role-id"
                                                        value="{{ $roles->first()->role_id ?? '' }}">

                                                    <!-- Dashboard Items Cards -->
                                                    <div class="row">
                                                        @foreach ($dashboardItems as $index => $item)
                                                            @php
                                                                $title = $item->title ?? 'N/A';
                                                                $icon = $item->icon ?? 'fe fe-bar-chart';
                                                                $colorClass = ['primary', 'warning', 'info', 'success'][
                                                                    $index % 4
                                                                ];
                                                                $countKey = $item->count_id ?? null;
                                                                $countValue = isset($$countKey) ? $$countKey : 0;
                                                            @endphp

                                                            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                                                <div class="card custom-card shadow-lg">
                                                                    <div class="card-body">
                                                                        <input type="hidden" name="db_id"
                                                                            value="{{ $item->db_id }}">
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="me-2">
                                                                                <div
                                                                                    class="avatar avatar-md bg-{{ $colorClass }}-transparent text-{{ $colorClass }}">
                                                                                    <i
                                                                                        class="{{ $icon }}"></i>
                                                                                </div>
                                                                            </div>
                                                                            <div class="flex-1">
                                                                                <div class="mg-b-6">
                                                                                    <p class="mb-0 tx-13 text-muted">
                                                                                        {{ $title }}</p>
                                                                                </div>
                                                                                <div class="flex-between">
                                                                                    <h3 class="tx-20 mb-0 font-weight-normal"
                                                                                        id="{{ $item->count_id }}">
                                                                                        {{ $countValue }}</h3>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mt-3 pt-2 border-top">
                                                                            <div class="form-check">
                                                                                <input
                                                                                    class="form-check-input square-checkbox"
                                                                                    type="checkbox"
                                                                                    id="permission-{{ $index }}"
                                                                                    name="dashboard_permissions[{{ $item->id }}]"
                                                                                    value="1">
                                                                                <label class="form-check-label"
                                                                                    for="permission-{{ $index }}">Role
                                                                                    Can View</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <!-- Additional Dashboard Items Section -->
                                                    <h5 class="mt-4 mb-3">Additional Permission</h5>
                                                    <div class="row">
                                                        @foreach ($AdditionaldashboardItems as $item)
                                                            <div class="col-md-3 mb-3">
                                                                <div class="card custom-card shadow-sm">
                                                                    <div
                                                                        class="card-body d-flex align-items-center justify-content-between">
                                                                        <span
                                                                            class="tx-14">{{ $item->title }}</span>
                                                                        <input type="checkbox"
                                                                            name="additional_dashboard_items[]"
                                                                            id="additional-{{ $item->id }}"
                                                                            value="{{ $item->id }}"
                                                                            class="form-check-input">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>



                                                    <!-- Save Button -->
                                                    <div class="row mt-4">
                                                        <div class="col-md-12 text-end">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fe fe-save"></i> Save Permissions
                                                            </button>
                                                        </div>
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
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete all permissions for role: <strong><span
                                id="deleteRoleName"></span></strong>?</p>
                    <input type="hidden" id="deleteRoleId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script src="{{ $actual_url . '/admin_assets/lead_count_pusher.js' }}"></script>
    <script>
        $(document).ready(function() {



            $('#role_id').select2({
                width: '100%'
            });




            function resetForm() {
                $('#PermissionForm')[0].reset();
                $('#is_edit').val('0');
                $('#form_role_id').val('');
                $('#edit_role_id').val('');
                $('#role_id').prop('disabled', false).val('').trigger('change');
                $('input[type="checkbox"]').prop('checked', false);
                $('.nav-link[href="#new-menu"]').text('New Permission');
                $('button[type="submit"]').html('<i class="fas fa-save me-2"></i> Save Permissions');
            }


            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#menu-list') {
                    resetForm();
                    if (typeof permissionTable !== 'undefined') {
                        permissionTable.ajax.reload();
                    }
                }
            });


            $('#PermissionForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let formData = form.serialize();
                let roleId = $('#is_edit').val() == '1' ? $('#edit_role_id').val() : $('#role_id').val();

                // Validate role selection
                if (!roleId) {
                    iziToast.info({
                        title: 'Note',
                        message: 'Please Select Role!',
                        position: 'topRight',
                        transitionIn: 'bounceInLeft',
                        transitionOut: 'fadeOutRight',
                        timeout: 2000
                    });
                    return false; // Stop form submission
                }

                $('#form_role_id').val(roleId);
                formData = form.serialize();

                $.ajax({
                    url: "{{ route('permissions/store') }}",
                    type: "POST",
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Success:', response);
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight',
                            transitionIn: 'bounceInLeft',
                            transitionOut: 'fadeOutRight',
                            timeout: 1000,
                            onClosing: function() {
                                $('.nav-link[href="#menu-list"]').tab('show');
                                resetForm();
                            }
                        });

                        if (typeof permissionTable !== 'undefined') {
                            permissionTable.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr.responseText);
                        let errorMessage = 'Something went wrong.';

                        // Check for validation errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Get first error message
                            errorMessage = Object.values(xhr.responseJSON.errors)[0][0];
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        iziToast.error({
                            title: 'Error',
                            message: errorMessage,
                            transitionIn: 'bounceInLeft',
                            transitionOut: 'fadeOutRight',
                            position: 'topRight',
                            timeout: 2000
                        });
                    }
                });
            });

            $('#deleteConfirmationModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var roleId = button.data('role-id');
                var roleName = button.data('role-name'); // Get role name from data attribute

                $('#deleteRoleId').val(roleId);
                $('#deleteRoleName').text(roleName);
            });

            var permissionTable = $('.menu-submenu-data').DataTable({
                "processing": false,
                "serverSide": true,
                "pageLength": 10,
                "responsive": true,
                "ajax": {
                    url: "{{ route('permissions/data') }}",
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    dataSrc: function(json) {
                        var dataArray = [];
                        $.each(json.data, function(index, item) {
                            const dataRow = [
                                item.sr_no,
                                item.role_name,
                                item.menu_title,
                                item.sub_menu_data,
                                item.action
                            ];
                            dataArray.push(dataRow);
                        });
                        return dataArray;
                    }
                },
                "columnDefs": [{
                    targets: 0,
                    orderable: false
                }]
            });

            $(document).on('click', '.getEdit', function(e) {
                e.preventDefault();

                resetForm();

                var role_id = $(this).data('role-id');
                var menu_id = $(this).data('menu-id');
                $('#is_edit').val('1');
                $('#edit_role_id').val(role_id);
                $('#form_role_id').val(role_id);
                var url = "{{ route('roleper_edit', ['role_id' => ':id']) }}";
                url = url.replace(':id', role_id);

                $('.nav-link[href="#new-menu"]').tab('show');
                $('.nav-link[href="#new-menu"]').text('Update Permission');
                $('button[type="submit"]').html('<i class="fas fa-save me-2"></i> Update');
                $('#role_id').prop('disabled', true).val(role_id).trigger('change');

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#role_id').val(role_id).trigger('change');
                        $('#role_id').prop('disabled', true);
                        $('input[type="checkbox"]').prop('checked', false);

                        response.role.forEach(function(item) {
                            if (!item.sub_menu_id) {
                                const menuId = item.menu_id;
                                $(`input[name="menu_view[${menuId}]"]`).prop('checked',
                                    item.can_view === 1);
                                $(`input[name="menu_create[${menuId}]"]`).prop(
                                    'checked', item.can_create === 1);
                                $(`input[name="menu_update[${menuId}]"]`).prop(
                                    'checked', item.can_update === 1);
                                $(`input[name="menu_delete[${menuId}]"]`).prop(
                                    'checked', item.can_delete === 1);
                            } else {
                                const menuId = item.menu_id;
                                const subMenuId = item.sub_menu_id;
                                $(`input[name="submenu_view[${menuId}][${subMenuId}]"]`)
                                    .prop('checked', item.can_view === 1);
                                $(`input[name="submenu_create[${menuId}][${subMenuId}]"]`)
                                    .prop('checked', item.can_create === 1);
                                $(`input[name="submenu_update[${menuId}][${subMenuId}]"]`)
                                    .prop('checked', item.can_update === 1);
                                $(`input[name="submenu_delete[${menuId}][${subMenuId}]"]`)
                                    .prop('checked', item.can_delete === 1);
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error('Error fetching role permissions:', xhr.responseText);
                        iziToast.error({
                            title: "Error!",
                            message: "Failed to fetch role permissions. Please try again.",
                            position: "topRight",
                        });
                    }
                });
            });

            $('#role_id').on('change', function() {
                let roleId = $(this).val();

                if (roleId) {
                    $.ajax({
                        url: "{{ route('permissions/get-role-permissions') }}",
                        type: "POST",
                        data: {
                            role_id: roleId,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('input[type="checkbox"]').prop('checked', false);

                            response.forEach(permission => {
                                if (permission.sub_menu_id) {
                                    if (permission.can_view) {
                                        $('input[name="submenu_view[' + permission
                                            .menu_id + '][' + permission
                                            .sub_menu_id + ']"]').prop('checked',
                                            true);
                                    }
                                    if (permission.can_create) {
                                        $('input[name="submenu_create[' + permission
                                            .menu_id + '][' + permission
                                            .sub_menu_id + ']"]').prop('checked',
                                            true);
                                    }
                                    if (permission.can_update) {
                                        $('input[name="submenu_update[' + permission
                                            .menu_id + '][' + permission
                                            .sub_menu_id + ']"]').prop('checked',
                                            true);
                                    }
                                    if (permission.can_delete) {
                                        $('input[name="submenu_delete[' + permission
                                            .menu_id + '][' + permission
                                            .sub_menu_id + ']"]').prop('checked',
                                            true);
                                    }
                                } else {
                                    if (permission.can_view) {
                                        $('input[name="menu_view[' + permission
                                            .menu_id + ']"]').prop('checked', true);
                                    }
                                    if (permission.can_create) {
                                        $('input[name="menu_create[' + permission
                                            .menu_id + ']"]').prop('checked', true);
                                    }
                                    if (permission.can_update) {
                                        $('input[name="menu_update[' + permission
                                            .menu_id + ']"]').prop('checked', true);
                                    }
                                    if (permission.can_delete) {
                                        $('input[name="menu_delete[' + permission
                                            .menu_id + ']"]').prop('checked', true);
                                    }
                                }
                            });
                        },
                        error: function(xhr) {
                            console.error('Error:', xhr.responseText);
                            alert('Failed to fetch permissions.');
                        }
                    });
                }
            });


            $('#confirmDeleteBtn').click(function() {
                var roleId = $('#deleteRoleId').val();

                $.ajax({
                    url: '{{ route('deleterolepermissions') }}',
                    type: 'POST',
                    data: {
                        role_id: roleId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#deleteConfirmationModal').modal('hide');

                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight',
                                animateInside: true,
                                transitionIn: 'bounceInLeft',
                                transitionOut: 'bounceOutRight',
                                transitionInMobile: 'bounceInLeft',
                                transitionOutMobile: 'bounceOutRight',
                                timeout: 700,
                                displayMode: 2,
                                onClosing: function() {
                                    $('.menu-submenu-data').DataTable().ajax
                                        .reload();
                                }
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight',
                                animateInside: true,
                                transitionIn: 'bounceInLeft',
                                transitionOut: 'bounceOutRight',
                                transitionInMobile: 'bounceInLeft',
                                transitionOutMobile: 'bounceOutRight',
                                timeout: 700
                            });
                        }
                    },
                    error: function(xhr) {
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred while deleting permissions',
                            position: 'topRight',
                            animateInside: true,
                            transitionIn: 'bounceInLeft',
                            transitionOut: 'bounceOutRight',
                            transitionInMobile: 'bounceInLeft',
                            transitionOutMobile: 'bounceOutRight',
                            timeout: 700
                        });
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const roleSelect = document.getElementById('role-select');
            const formRoleId = document.getElementById('form-role-id');
            const permissionForm = document.getElementById('permission-form');


            $('#role-select').select2({
                width: '100%'
            });

            function init() {
                roleSelect.addEventListener('change', handleRoleChange);
                permissionForm.addEventListener('submit', handleFormSubmit);

                // Load permissions if role is already selected
                if (roleSelect.value) {
                    loadPermissions(roleSelect.value);
                }
            }

            async function loadPermissions(roleId) {
                if (!roleId) return;

                try {
                    const dbIds = Array.from(document.querySelectorAll('input[name="db_id"]'))
                        .map(input => input.value)
                        .filter((value, index, self) => self.indexOf(value) === index);

                    if (dbIds.length === 0) return;

                    document.querySelectorAll('.square-checkbox').forEach(checkbox => {
                        checkbox.checked = false;
                    });

                    const response = await fetch(
                        `{{ route('permissionsload') }}?role_id=${roleId}&db_ids=${dbIds.join(',')}`);
                    const data = await response.json();

                    if (data.permissions) {
                        data.permissions.forEach(permission => {
                            const card = document.querySelector(
                                `input[name="db_id"][value="${permission.db_id}"]`)?.closest(
                                '.card.custom-card');
                            if (card) {
                                const checkbox = card.querySelector('.square-checkbox');
                                if (checkbox) {
                                    checkbox.checked = permission.can_view == 1;
                                }
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error loading permissions:', error);
                    showToast('error', 'Failed to load permissions');
                }
            }

            function handleRoleChange() {
                const selectedRoleId = this.value;
                formRoleId.value = selectedRoleId;
                loadPermissions(selectedRoleId);
            }

            async function handleFormSubmit(e) {
                e.preventDefault();


                const permissionsMap = new Map();

                document.querySelectorAll('.card.custom-card').forEach(card => {
                    const dbId = card.querySelector('input[name="db_id"]').value;
                    const checkbox = card.querySelector('.square-checkbox');
                    const dashboardId = checkbox.name.match(/\[(.*?)\]/)[1];


                    if (!permissionsMap.has(dbId) && checkbox.checked) {
                        permissionsMap.set(dbId, {
                            db_id: dbId,
                            dashboard_id: dashboardId,
                            can_view: 1
                        });
                    }
                });


                const permissions = Array.from(permissionsMap.values());

                try {
                    const response = await fetch("{{ route('permissionssave') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            role_id: roleSelect.value,
                            permissions: permissions
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast('success', result.message || 'Permissions saved successfully!');
                    } else {
                        showToast('error', result.message || 'Error saving permissions');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while saving permissions');
                }
            }

            function showToast(type, message) {
                const toastConfig = {
                    title: type.charAt(0).toUpperCase() + type.slice(1),
                    message: message,
                    position: 'topRight',
                    timeout: 3000
                };

                if (type === 'success') {
                    iziToast.success(toastConfig);
                } else {
                    iziToast.error(toastConfig);
                }
            }

            init();
        });
    </script>

</body>

</html>
