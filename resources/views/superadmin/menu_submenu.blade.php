<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient" data-menu-styles="light">
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
                                            <ul class="nav nav-pills justify-content-left nav-style-2 mb-1" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#menu-list" aria-selected="true">List</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#new-menu" aria-selected="false">New
                                                        Menu</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#menu-ordering" aria-selected="false">Menu
                                                        Ordering</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane border-0 show active text-muted px-1" id="menu-list" role="tabpanel">
                                                    <div class="row">
                                                        <table id="datatable-basic" class="table table-bordered  menu-submenu-data" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width:1%;">#</th>
                                                                    <th style="width:20%">Menu</th>
                                                                    <th style="width:69%">Submenu</th>
                                                                    <th class="text-center" style="width:10%">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody></tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="tab-pane text-muted border-0 px-1" id="new-menu" role="tabpanel">
                                                    <form id="MenuForm" class="needs-validation" novalidate>
                                                        @csrf
                                                        <input type="hidden" name="c_menu_id" id="c_menu_id" value="">
                                                        <div class="row">
                                                            <div class="col-md-2 col-6">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Menu Type<span style="color:red;">*</span></label>
                                                                    <select class="js-example-placeholder-single js-states form-control" id="menu_type_id" name="menu_type_id" required>
                                                                        <option value="" selected disabled>Select Menu
                                                                            Type
                                                                        </option>
                                                                        @foreach ($menu_type as $menu)
                                                                        <option value="{{ $menu->menu_type_id }}">
                                                                            {{ $menu->type_name }}
                                                                        </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <div class="invalid-feedback">Menu Type is required
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3 col-6">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Menu Name <span style="color:red;">*</span></label>
                                                                    <input type="text" class="form-control" id="menu_title" placeholder="Enter Menu Name" name="menu_title" required>
                                                                    <div class="invalid-feedback">Menu Name is required
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3 col-6">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Menu Routes</label>
                                                                    <input type="text" class="form-control" id="menu_route" placeholder="Enter Menu Routes" name="menu_route">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2 col-6">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Menu Icon</label>
                                                                    <input type="text" class="form-control" id="menu_icon" placeholder="Enter Menu Icon" name="menu_icon">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2 col-12">
                                                                <div class="mb-2 mt-4">
                                                                    <!-- Hidden input to send value as 0 when the checkbox is unchecked -->
                                                                    <input type="hidden" name="is_submenu" value="0">
                                                                    <input type="checkbox" id="is_submenu" class="form-check-input" name="is_submenu" value="1">
                                                                    <label for="is_submenu">Menu Have Submenu</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div id="submenu_fields" name="submenu_fields" style="display: none;">
                                                            <div class="submenu_row">
                                                                <div class="row">
                                                                    <div class="col-md-3 col-5">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Submenu Name <span style="color:red;">*</span></label>
                                                                            <input type="text" class="form-control" placeholder="Enter Submenu Name" name="sub_menu_title[]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3 col-5">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Submenu Routes <span style="color:red;">*</span></label>
                                                                            <input type="text" class="form-control" placeholder="Enter Submenu Routes" name="sub_menu_route[]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3 col-2">
                                                                        <button type="button" class="mt-4 btn btn-sm btn-success add"><i class="bx bx-plus"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3">
                                                            <div class="col-md-12 text-end">
                                                                <button type="submit" class="btn btn-primary">Submit</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="tab-pane fade border-0 px-1" id="menu-ordering" role="tabpanel">
                                                    <div class="mt-3">
                                                        <div class="" id="sortable-menu"></div>
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
        @include('include/footer')
    </div>
    @include('include/footer_links')
    @include('include/datatable_js_link')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
     <script>
        $(document).ready(function () {
            //  $('.tooltip-init').tooltip();
            $('#menu_type_id').select2({
                width: '100%'
            });
            $('#is_submenu').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#submenu_fields').show();
                    $('#submenu_fields input[name="sub_menu_title[]"]').prop('required', true);
                    $('#submenu_fields input[name="sub_menu_route[]"]').prop('required', true);
                } else {
                    $('#submenu_fields').hide();
                    $('#submenu_fields input[name="sub_menu_title[]"]').prop('required', false);
                    $('#submenu_fields input[name="sub_menu_route[]"]').prop('required', false);
                }
            });
            if ($('#is_submenu').is(':checked')) {
                $('#submenu_fields').show();
                $('#submenu_fields input[name="sub_menu_title[]"]').prop('required', true);
                $('#submenu_fields input[name="sub_menu_route[]"]').prop('required', true);
            }
            // Add new submenu row
            $(document).on('click', '.add', function () {
                const html = `
            <div class="row submenu_row">
                <div class="col-md-3 col-5">
                    <div class="mb-2">
                        <label class="form-label">Submenu Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Enter Submenu Name" name="sub_menu_title[]" ${$('#is_submenu').is(':checked') ? 'required' : ''}>
                    </div>
                </div>
                <div class="col-md-3 col-5">
                    <div class="mb-2">
                        <label class="form-label">Submenu Routes <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Enter Submenu Routes" name="sub_menu_route[]" ${$('#is_submenu').is(':checked') ? 'required' : ''}>
                    </div>
                </div>
                <div class="col-md-3 col-2">
                    <button type="button" class="mt-4 btn btn-sm btn-danger remove"><i class="bx bx-minus"></i></button>
                </div>
            </div>
        `;
                $('#submenu_fields').append(html);
            });
            // $(document).on('click', '.remove', function () {
            //     $(this).closest('.submenu_row').remove();
            // });

            $(document).on('click', '.remove', function () {
    let row = $(this).closest('.submenu_row');
    let submenuId = row.data('id');

    if (submenuId) {
        // build dynamic url
        let url = "{{ route('submenu.delete', ':id') }}".replace(':id', submenuId);

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    row.remove();
                    iziToast.success({
                        title: 'Success',
                        message: 'Submenu deleted successfully',
                        position: 'topRight'
                    });
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: response.message || 'Failed to delete submenu',
                        position: 'topRight'
                    });
                }
            }
        });
    } else {
        row.remove();
    }
});

            var MenuData = $('.menu-submenu-data').DataTable({
                "processing": false,
                "serverSide": true,
                "pageLength": 10,
                "responsive": false,
                "ajax": {
                    url: "{{ route('menudatafetch') }}",
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    dataSrc: function (json) {
                        var dataArray = [];
                        $.each(json.data, function (index, item) {
                            const dataRow = [
                                item.sr_no, item.menu_title, item.sub_menu_title, item
                                    .Action
                            ];
                            dataArray.push(dataRow);
                        });
                        return dataArray;
                    }
                },
                "columnDefs": [{
                    targets: 0,
                    orderable: false
                }],
            });
            $('.menu-submenu-data').on('draw.dt', function () {
                $('.tooltip-init').tooltip();
            });
            $('#MenuForm').on('submit', function (e) {
                e.preventDefault();
                if ($(this)[0].checkValidity() === false) {
                    $(this).addClass('was-validated');
                    return;
                }
                let formData = $(this).serialize();
                let url = "{{ route('menustore') }}";
                let method = "POST";
                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight',
                                transitionIn: 'bounceInLeft',
                                transitionOut: 'fadeOutRight',
                                timeout: 1000,
                                onClosing: function () {
                                    setTimeout(function () {
                                        $('#MenuForm')[0].reset();
                                        $('#MenuForm').removeClass('was-validated');
                                        $('.nav-link[href="#menu-list"]').tab('show');
                                        MenuData.ajax.reload(null, false);
                                    }, 0);
                                }
                            });
                        } else {
                            iziToast.warning({
                                title: 'Warning',
                                message: response.message,
                                position: 'topRight',
                                transitionIn: 'bounceInLeft',
                            });
                        }
                    },
                    error: function (xhr) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = "";
                        for (let key in errors) {
                            errorMessage += errors[key][0] + "<br>";
                        }
                        iziToast.error({
                            title: 'Error',
                            message: errorMessage,
                            position: 'topRight',
                            transitionOut: 'fadeOutRight',
                            timeout: 5000
                        });
                    }
                });
            });
            $(document).on('click', '#getEdit', function (e) {
                e.preventDefault();
                var c_menu_id = $(this).data('c_menu_id');
                $('.nav-pills a[href="#new-menu"]').tab('show');
                $('#MenuForm')[0].reset();
                $('#c_menu_id').val(c_menu_id);
                $('#submenu_fields').html(`
        <div class="submenu_row">
            <div class="row">
                <div class="col-md-3 col-5">
                    <div class="mb-2">
                        <label class="form-label">Submenu Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Enter Submenu Name" name="sub_menu_title[]">
                    </div>
                </div>
                <div class="col-md-3 col-5">
                    <div class="mb-2">
                        <label class="form-label">Submenu Routes <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" placeholder="Enter Submenu Routes" name="sub_menu_route[]">
                    </div>
                </div>
                <div class="col-md-3 col-2">
                    <button type="button" class="mt-4 btn btn-sm btn-success add"><i class="bx bx-plus"></i></button>
                </div>
            </div>
        </div>
    `);
                $.ajax({
                    url: "{{ route('getMenuData') }}",
                    type: "GET",
                    data: {
                        c_menu_id: c_menu_id
                    },
                    success: function (response) {
                        if (response.success) {
                            $('#menu_type_id').val(response.menu.menu_type_id).trigger(
                                'change');
                            $('#menu_title').val(response.menu.menu_title);
                            $('#menu_route').val(response.menu.menu_route);
                            $('#menu_icon').val(response.menu.menu_icon);
                            if (response.menu.is_submenu == 1) {
                                $('#is_submenu').prop('checked', true);
                                $('#submenu_fields').show();
                                $('#submenu_fields').html('');
                                if (response.submenus.length > 0) {
                                    $.each(response.submenus, function (index, submenu) {
                                        let removeButton = index === 0 ?
                                            '<button type="button" class="mt-4 btn btn-sm btn-success add"><i class="bx bx-plus"></i></button>' :
                                            '<button type="button" class="mt-4 btn btn-sm btn-danger remove"><i class="bx bx-minus"></i></button>';
                                        let submenuHtml = `
                               <div class="submenu_row" data-id="${submenu.c_sub_menu_id}">
                                    <div class="row">
                                        <div class="col-md-3 col-5">
                                            <div class="mb-2">
                                                <label class="form-label">Submenu Name <span style="color:red;">*</span></label>
                                                <input type="text" class="form-control" placeholder="Enter Submenu Name"
                                                    name="sub_menu_title[]" value="${submenu.sub_menu_title}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-5">
                                            <div class="mb-2">
                                                <label class="form-label">Submenu Routes <span style="color:red;">*</span></label>
                                                <input type="text" class="form-control" placeholder="Enter Submenu Routes"
                                                    name="sub_menu_route[]" value="${submenu.sub_menu_route}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-2">
                                            ${removeButton}
                                        </div>
                                    </div>
                                </div>
                            `;
                                        $('#submenu_fields').append(submenuHtml);
                                    });
                                }
                            } else {
                                $('#is_submenu').prop('checked', false);
                                $('#submenu_fields').hide();
                            }
                            $('#MenuForm button[type="submit"]').text('Update');
                            $('html, body').animate({
                                scrollTop: $('#MenuForm').offset().top - 100
                            }, 500);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message ||
                                    'Failed to fetch menu data',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function (xhr) {
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred while fetching menu data',
                            position: 'topRight'
                        });
                        console.error(xhr.responseText);
                    }
                });
            });
            $(document).on('click', '.delete-btn', function () {
                currentMenuIdToDelete = $(this).data('menu-id');
                const menuTitle = $(this).data('menu-title');
                $('#menuTitleToDelete').text(menuTitle);
            });
        });
        $(document).on('click', '#confirmDelete', function () {
            if (!currentMenuIdToDelete) return;
            $.ajax({
                url: "{{ route('deleteMenu') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    c_menu_id: currentMenuIdToDelete
                },
                beforeSend: function () {
                    $('#confirmDelete').prop('disabled', true).html(
                        '<i class="bx bx-loader bx-spin"></i> Deleting...');
                },
                success: function (response) {
                    if (response.success) {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight',
                            transitionIn: 'bounceInLeft',
                            transitionOut: 'fadeOutRight',
                            timeout: 1000,
                            onClosing: function () {
                                setTimeout(function () {
                                    $('#MenuForm')[0].reset();
                                    $('#MenuForm').removeClass('was-validated');
                                    $('.nav-link[href="#menu-list"]').tab('show');
                                    $('.modal').modal('hide');
                                    $('.menu-submenu-data').DataTable().ajax.reload(
                                        null, false);
                                }, 0);
                            }
                        });
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: response.message,
                            position: 'topRight'
                        });
                    }
                },
                error: function (xhr) {
                    iziToast.error({
                        title: 'Error',
                        message: 'An error occurred while deleting the menu',
                        position: 'topRight'
                    });
                    console.error(xhr.responseText);
                },
                complete: function () {
                    $('#confirmDelete').prop('disabled', false).text('Delete');
                    currentMenuIdToDelete = null;
                }
            });
        });
        function loadMenus() {
            $.ajax({
                url: "{{ route('menusorderingfetch') }}",
                type: "GET",
                success: function (data) {
                    $('#sortable-menu').html(''); // Clear previous data
                    if (!Array.isArray(data) || data.length === 0) {
                        $('#sortable-menu').html('<p>No menus found</p>'); // Handle no data scenario
                        return;
                    }
                    $.each(data, function (index, item) {
                        let submenuHtml = '';
                        // Check if submenus exist and are in correct format
                        if (Array.isArray(item.submenus) && item.submenus.length > 0) {
                            $.each(item.submenus, function (subIndex, submenu) {
                                submenuHtml += `
            <div class="p-2 border rounded mb-1 d-flex align-items-center justify-content-between sortable-submenu-item" data-id="${submenu.c_sub_menu_id}" style="cursor: grab;">
                ${submenu.sub_menu_title}
                <i class="fas fa-grip-lines"></i>
            </div>`;
                            });
                        }
                        // Append the menu item with submenus
                        $('#sortable-menu').append(`
                    <div class="col-sm-6 col-md-4 mb-3">
                        <div class="border p-2 rounded shadow-sm menu-item" data-id="${item.c_menu_id}" style="cursor: grab;">
                            <div class="card border-0 mb-0">
                                <div class="card-body p-1">
                                    <h5 class="card-title mb-0">${item.menu_title}</h5>
                                    <div class="">
                                            <div class="ms-3 sortable-submenu">
                                                ${submenuHtml}
                                            </div>
                                        <i class="fas fa-hand-rock fa-lg text-secondary mt-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                    });
                    initSortables(); // Re-initialize sortable logic after rendering
                },
                error: function (xhr, status, error) {
                    console.error("Error loading menus: ", status, error);
                    $('#sortable-menu').html('<p>Failed to load menus</p>'); // Show error message if AJAX fails
                }
            });
        }
        function initSortables() {
            // Menu sorting
            $('#sortable-menu').sortable({
                update: function (event, ui) {
                    let order = [];
                    $('#sortable-menu > .col-sm-6').each(function (index) {
                        order.push({
                            id: $(this).find('.menu-item').data('id'),
                            order: index + 1
                        });
                    });
                    $.ajax({
                        url: "{{ route('menusorderingupdate') }}",
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            order: JSON.stringify(order) // Stringify the array
                        },
                        success: function (response) {
                            console.log('Menu order updated', response);
                        },
                        error: function (xhr, status, error) {
                            console.error("Error updating menu order: ", status, error);
                        }
                    });
                }
            });
            // Submenu sorting
            $('.sortable-submenu').sortable({
                connectWith: '.sortable-submenu',
                update: function (event, ui) {
                    let order = [];
                    $(this).children('.sortable-submenu-item').each(function (index) {
                        order.push({
                            id: $(this).data('id'),
                            order: index + 1
                        });
                    });
                    // Get the parent menu ID
                    let menuId = $(this).closest('.menu-item').data('id');
                    $.ajax({
                        url: "{{ route('submenus/ordering/update') }}",
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            order: JSON.stringify(order), // Stringify the array
                            c_menu_id: menuId
                        },
                        success: function (response) {
                            console.log('Submenu order updated', response);
                        },
                        error: function (xhr, status, error) {
                            console.error("Error updating submenu order: ", status, error);
                        }
                    });
                }
            });
        }
        // Load menus when tab is shown
        $('a[href="#menu-ordering"]').on('shown.bs.tab', function () {
            loadMenus();
        });
    </script>
</body>
</html>
