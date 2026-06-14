<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient" data-menu-styles="light">
<head>
    @include('include/meta_tags')
    @include('include/header_links')
    <style>
        .loading-placeholder td {
            padding: 0.85rem 0.5rem;
        }

        .placeholder {
            background-color: #e9ecef;
            border-radius: 0.25rem;
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
                    <div class="col-xl-12">
                        <div class="row row-cols-xxl-5 row-cols-xl-4 row-cols-md-3">
                            <div class="col card-background flex-fill">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div>
                                                <p class="fw-medium mb-1 text-muted">Total Sales hi</p>
                                                <h3 class="mb-0">$18,645</h3>
                                            </div>
                                            <div class="avatar avatar-md br-4 bg-primary-transparent ms-auto">
                                                <i class="bi bi-cart-check fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex mt-2">
                                            <span class="badge bg-primary-transparent rounded-pill">+24% <i class="fe fe-arrow-down"></i></span>
                                            <a href="javascript:void(0);" class="text-muted fs-11 ms-auto text-decoration-underline mt-auto">view
                                                more</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col card-background flex-fill">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div>
                                                <p class="fw-medium mb-1 text-muted">Total Revenue</p>
                                                <h3 class="mb-0">$34,876</h3>
                                            </div>
                                            <div class="avatar avatar-md br-4 bg-secondary-transparent ms-auto">
                                                <i class="bi bi-archive fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex mt-2">
                                            <span class="badge bg-success-transparent rounded-pill">+0.26% <i class="fe fe-arrow-down"></i></span>
                                            <a href="javascript:void(0);" class="text-muted fs-11 ms-auto text-decoration-underline mt-auto">view
                                                more</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col card-background flex-fill">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div>
                                                <p class="fw-medium text-muted mb-1">Total Products</p>
                                                <h3 class="mb-0">26,231</h3>
                                            </div>
                                            <div class="avatar avatar-md br-4 bg-info-transparent ms-auto">
                                                <i class="bi bi-handbag fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex mt-2">
                                            <span class="badge bg-danger-transparent rounded-pill">+06% <i class="fe fe-arrow-down"></i></span>
                                            <a href="javascript:void(0);" class="text-muted fs-11 ms-auto text-decoration-underline mt-auto">view
                                                more</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col card-background flex-fill">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div>
                                                <p class="fw-medium mb-1 text-muted">Total Expenses</p>
                                                <h3 class="mb-0">$73,579</h3>
                                            </div>
                                            <div class="avatar avatar-md br-4 bg-warning-transparent ms-auto">
                                                <i class="bi bi-currency-dollar fs-20"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex mt-2">
                                            <span class="badge bg-success-transparent rounded-pill">+10% <i class="fe fe-arrow-up"></i></span>
                                            <a href="javascript:void(0);" class="text-muted fs-11 ms-auto text-decoration-underline mt-auto">view
                                                more</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                 


                    <div class="col-xl-6" id="reorder-alert-container" style="display: none;height:100% !important;">
                        <div class="card custom-card" style="height: 260px;">
                            <div class="bg-primary text-white px-2 py-1">
                                <div class="card-title mb-0">
                                    <h5 class="mb-0">Reorder Alerts</h5>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" id="reorder-alert-table-container">
                                    <table class="table text-nowrap" id="reorder-alert-table">
                                        <thead class="">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Raw Material</th>
                                                <th scope="col" class="text-center">Available Qty.</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reorder-alert-body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-xl-6 col-md-4 col-6" id="work-order-alert-container" style="display: none; height:100% !important;">
                        <div class="card custom-card" style="height: 260px;"> 
                            <div class="bg-primary text-white px-2 py-1">
                                <div class="card-title mb-0">
                                    <h5 class="mb-0">Work Order Alerts</h5>
                                </div>
                            </div>
                            <div class="card-body p-0" style="height: calc(100% - 40px);">
                                <div class="table-responsive" id="work-order-alert-table-container" style="height: 100%; overflow-y: auto;">
                                    <table class="table text-nowrap mb-0" id="work-order-alert-table">
                                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 2;">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Raw Material</th>
                                                <th scope="col" class="text-center">Qty.</th>
                                                <th scope="col">Work Order</th>
                                            </tr>
                                        </thead>
                                        <tbody id="work-order-alert-body">
                                            <!-- dynamic rows here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-md-4 col-6" id="present-staff-container">
                        <div class="card custom-card" style="height: 260px;">
                            <div class="bg-success text-white px-2 py-1">
                                <div class="card-title mb-0">
                                    <h5 class="mb-0">Today's Present Staff List ({{ \Carbon\Carbon::today()->format('d M Y') }})</h5>
                                </div>
                            </div>
                            <div class="card-body p-0" style="height: calc(100% - 40px);">
                                <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                    <table class="table text-nowrap mb-0">
                                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 2;">
                                            <tr>
                                                <th scope="col">Staff</th>
                                                <th scope="col">Time In</th>
                                                <th scope="col">Time Out</th>
                                                <th scope="col" class="text-center">Image</th>
                                            </tr>
                                        </thead>
                                        <tbody id="present-staff-body">
                                            </tbody>
                                    </table>
                                    <div id="no-present-staff" class="text-center p-3 text-muted" style="display: none;">
                                        No staff clocked in yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(Session::get('login_type') === 'Company')
                    <div class="col-xl-3 col-md-3 col-6" id="documentsExpiryCard" style="display: none;">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Documents expiry</div>
                                </div>
                                <div class="card-body p-0">
                                    <!-- Remove padding to control height precisely -->
                                    <div id="expiringDocumentsContainer" style="height: 210px;overflow: hidden;padding: 5px 10px;">
                                        <!-- Fixed height -->
                                        <!-- Skeleton Loader - Exactly 3 items -->
                                        <ul class="mb-0">
                                            <!-- Document 1 -->
                                            <li class="p-3 border-bottom">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <span class="avatar avatar-sm rounded-circle bg-primary-transparent text-primary placeholder-wave">
                                                            <i class="fe fe-file-text opacity-0"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="placeholder-wave">
                                                            <span class="placeholder col-8 rounded-1 mb-1" style="height: 16px;"></span>
                                                            <div class="d-flex align-items-center mt-2">
                                                                <span class="placeholder col-4 rounded-1 me-2" style="height: 14px;"></span>
                                                                <span class="placeholder col-3 rounded-1" style="height: 14px;"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="min-w-fit-content text-end">
                                                        <span class="placeholder col-5 rounded-1" style="height: 14px;"></span>
                                                    </div>
                                                </div>
                                            </li>

                                            <!-- Document 2 -->
                                            <li class="p-3 border-bottom">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <span class="avatar avatar-sm rounded-circle bg-primary-transparent text-primary placeholder-wave">
                                                            <i class="fe fe-file-text opacity-0"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="placeholder-wave">
                                                            <span class="placeholder col-6 rounded-1 mb-1" style="height: 16px;"></span>
                                                            <div class="d-flex align-items-center mt-2">
                                                                <span class="placeholder col-5 rounded-1 me-2" style="height: 14px;"></span>
                                                                <span class="placeholder col-4 rounded-1" style="height: 14px;"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="min-w-fit-content text-end">
                                                        <span class="placeholder col-5 rounded-1" style="height: 14px;"></span>
                                                    </div>
                                                </div>
                                            </li>

                                            <!-- Document 3 -->
                                            <li class="p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <span class="avatar avatar-sm rounded-circle bg-primary-transparent text-primary placeholder-wave">
                                                            <i class="fe fe-file-text opacity-0"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="placeholder-wave">
                                                            <span class="placeholder col-7 rounded-1 mb-1" style="height: 16px;"></span>
                                                            <div class="d-flex align-items-center mt-2">
                                                                <span class="placeholder col-3 rounded-1 me-2" style="height: 14px;"></span>
                                                                <span class="placeholder col-5 rounded-1" style="height: 14px;"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="min-w-fit-content text-end">
                                                        <span class="placeholder col-5 rounded-1" style="height: 14px;"></span>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                    </div>
                    @endif


                </div>
            </div>
        </div>
        <div id="workorderModal" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div id="content-data"></div>
            </div>
        </div>
        @include('include/footer')
    </div>

    @include('include/footer_links')

    


</body>
</html>

