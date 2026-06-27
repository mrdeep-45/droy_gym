<?php

use App\Http\Controllers\Attendance;
use App\Http\Controllers\Company;
use App\Http\Controllers\Company_menu_sub_menu;
use App\Http\Controllers\Company_setting;
use App\Http\Controllers\CRM_dashboard;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Currency;
use App\Http\Controllers\Customer;
use App\Http\Controllers\Documents;
use App\Http\Controllers\Get_data;
use App\Http\Controllers\Lead;
use App\Http\Controllers\LeadSource;
use App\Http\Controllers\LeadStatus;
use App\Http\Controllers\Service;
use App\Http\Controllers\Salary;
use App\Http\Controllers\Deal;
use App\Http\Controllers\Login;
use App\Http\Controllers\Quotation;
use App\Http\Controllers\MailConfig;
use App\Http\Controllers\Role_permission;
use App\Http\Controllers\Staff;
use App\Http\Middleware\SuperAdminIsValid;
use App\Http\Middleware\CompanyIsValid;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin;
use App\Http\Controllers\Super_menu_submenu;
use App\Http\Controllers\Raw_material;
use App\Http\Controllers\Supplier;
use App\Http\Controllers\Purchase;
use App\Http\Controllers\Purchase_inward;
use App\Http\Controllers\CompanyDocument;
use App\Http\Controllers\StockGroup;
use App\Http\Controllers\StockCategory;
use App\Http\Controllers\Qty_set;
use App\Http\Controllers\Product;
use App\Http\Controllers\Product_mapping;
use App\Http\Controllers\Vendor;
use App\Http\Controllers\Work_order;
use App\Http\Controllers\Material_issue;
use App\Http\Controllers\Production;
use App\Http\Controllers\Hsn_Gst;
use App\Http\Controllers\Country;
use App\Http\Controllers\State;
use App\Http\Controllers\City;
use App\Http\Controllers\Unit;
use App\Http\Controllers\Altunit;
use App\Http\Controllers\Gst;
use App\Http\Controllers\Category;
use App\Http\Controllers\Terms;
use App\Http\Controllers\DealstageController;
use App\Http\Controllers\Leave;
use App\Http\Controllers\Purchase_return;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\Complaint;
use App\Http\Controllers\Inventory_report;
use App\Http\Controllers\Credit_report;
use App\Http\Middleware\CheckStaffPermission;
use App\Http\Controllers\Product_category;
use App\Http\Controllers\AMC_tracking;
use App\Http\Controllers\Expense_type;
use App\Http\Controllers\Bank;
use App\Http\Controllers\Dispatch;
use App\Http\Controllers\Invoice;
use App\Http\Controllers\Internal;
use App\Http\Controllers\Final_qc;
use App\Http\Controllers\Scrap;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\OpenLead;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\TrainerController;

// Route::get('/', function () {
//     return view('welcome');
// });


Route::get('/', [Login::class, 'index'])->name('login');
Route::post('login_process', [Login::class, 'login_process'])->name('login_process');
Route::get('logout', [Login::class, 'logout'])->name('logout');

Route::get('companies_edit/{id}', [Company::class, 'edit'])->name('companies.edit');
Route::post('company/store', [Company::class, 'store'])->name('companies/store');
Route::get('quick/search', [Login::class, 'quick_search'])->name('quick.search');
Route::get('/send-static-invoice-whatsapp', [Lead::class, 'sendStaticInvoiceWhatsApp'])->name('send-static-invoice-whatsapp');

 Route::get('/attendancelist/export/excel', [Superadmin::class, 'exportExcel'])->name('attendance.export.excel');
Route::get('/attendancelist/export/pdf', [Superadmin::class, 'exportPdf'])->name('attendance.export.pdf');

Route::get('showledgerreport/excel', [Superadmin::class, 'exportLedgerExcel'])->name('showledgerreport.excel');
Route::get('showledgerreport/pdf', [Superadmin::class, 'exportLedgerPdf'])->name('showledgerreport.pdf');

Route::get('detailedattendancereport/pdf', [Superadmin::class, 'exportDetailedAttendancePdf'])->name('showDetailedAttendanceReport.pdf');
 Route::get('detailedattendancereport/excel', [Superadmin::class, 'exportDetailedAttendanceExcel'])->name('showDetailedAttendanceReport.excel');

Route::middleware([SuperAdminIsValid::class])->controller(Superadmin::class)->group(function () {
    Route::get('dashboard', [Superadmin::class, 'index'])->name('dashboard');
    Route::get('company', [Company::class, 'index'])->name('company');
    Route::get('companies/list', [Company::class, 'list'])->name('companies/list');

    //add new for forget request
    // Route::get('/superadmin/forgot-timeout-requests', [Superadmin::class, 'showForgotTimeoutRequests'])->name('superadmin.forgot_timeout.requests');
    // Route::post('/superadmin/forgot-timeout-approve', [Superadmin::class, 'approveForgotTimeout'])->name('superadmin.forgot_timeout.update');
    // Route::post('/superadmin/forgot-timeout-reject', [Superadmin::class, 'rejectForgotTimeout'])->name('superadmin.forgot_timeout.reject');



    // Role and permission

    Route::get('company/role', [Super_menu_submenu::class, 'index'])->name('company/role');
    Route::post('menustore', [Super_menu_submenu::class, 'menu_store'])->name('menustore');
    Route::get('menudatafetch', [Super_menu_submenu::class, 'menu_data_fetch'])->name('menudatafetch');
    Route::post('getmenudata/{menu_id}', [Super_menu_submenu::class, 'get_menu_data'])->name('getmenudata');
    Route::post('submenudelete', [Super_menu_submenu::class, 'delete'])->name('submenudelete');
    Route::post('deleteMenu', [Super_menu_submenu::class, 'deleteMenu'])->name('deleteMenu');
    Route::post('dashboard-settings', [Super_menu_submenu::class, 'saveSettings'])->name('dashboardsave');

    // Menu Order
    Route::get('menuorder', [Super_menu_submenu::class, 'menu_order'])->name('menuorder');
    Route::post('updatemenuorder', [Super_menu_submenu::class, 'updateMenuOrder'])->name('updatemenuorder');
    Route::post('updatesubmenuorder', [Super_menu_submenu::class, 'updateSubmenuOrder'])->name('updatesubmenuorder');
    Route::get('get-menu-data', [Super_menu_submenu::class, 'getMenuData'])->name('getMenuData');
    Route::get('menusorderingfetch', [Super_menu_submenu::class, 'fetchOrdering'])->name('menusorderingfetch');
    Route::post('menusorderingupdate', [Super_menu_submenu::class, 'updateOrdering'])->name('menusorderingupdate');
    Route::post('submenus/ordering/update', [Super_menu_submenu::class, 'updateSubmenuOrdering'])->name('submenus/ordering/update');
    Route::post('submenu/delete/{id}', [Super_menu_submenu::class, 'destroy'])->name('submenu.delete');
    Route::get('changepassword', [Login::class, 'change_password'])->name('changepassword');
    Route::post('UpdatePass', [Login::class, 'update_password'])->name('UpdatePass');
});


Route::middleware([CompanyIsValid::class])->controller(Company::class)->group(function () {
    Route::post('/commondelete/{id}', [Company::class, 'destroy'])->name('commondelete');

    // Menu & Sub Menu
    Route::get('menu/submenu', [Role_permission::class, 'index'])->name('menu/submenu')->middleware([CheckStaffPermission::class]);
    Route::post('save/permissions', [Role_permission::class, 'save_permissions'])->name('save.permissions');
    Route::get('get-permissions/{roleId}', [Role_permission::class, 'get_permissions'])->name('get.permissions');
    Route::post('role/create', [Role_permission::class, 'create_role'])->name('role.create');
    Route::get('permissions/list', [Role_permission::class, 'list'])->name('permissions.list');
    Route::post('permissions/delete', [Role_permission::class, 'delete'])->name('permissions.delete');
    Route::get('company/dashboard', [Company::class, 'dashboard'])->name('company/dashboard');


    Route::get('crm/dashboard', [CRM_dashboard::class, 'index'])->name('crm.dashboard')->middleware([CheckStaffPermission::class]);
    Route::get('deals/stage/chart', [CRM_dashboard::class, 'get_deal_counts_stage'])->name('deals.stage.chart');
    Route::get('deal/close/list', [CRM_dashboard::class, 'list_deal_close'])->name('deal.close.list');

    Route::get('get/country', [Company::class, 'getCountries'])->name('get.country');
    Route::get('get/state', [Company::class, 'getStates'])->name('get.state');
    Route::get('get/city', [Company::class, 'getCities'])->name('get.city');

    Route::get('get/customer/country', [Get_data::class, 'get_customer_Countries'])->name('get.customer.country');
    Route::get('get/customer/state', [Get_data::class, 'get_customer_States'])->name('get.customer.state');
    Route::get('get/customer/city', [Get_data::class, 'get_customer_Cities'])->name('get.customer.city');


    // Company Profile Setting
    Route::get('staff', [Staff::class, 'index'])->name('staff')->middleware([CheckStaffPermission::class]);
    Route::post('staff/store', [Staff::class, 'store'])->name('staff.store');
    Route::get('staff/list', [Staff::class, 'list'])->name('staff.list');
    Route::get('getstaffdata', [Staff::class, 'get_staff'])->name('getstaffdata');

    // Route::post('staff/update/{id}', [Staff::class, 'update'])->name('staff.update');



    Route::post('/staff/update', [Staff::class, 'update'])->name('staff.update');



    // Lead By MT
    Route::get('lead', [Lead::class, 'index'])->name('lead')->middleware([CheckStaffPermission::class]);
    Route::post('lead/store', [Lead::class, 'store'])->name('lead.store');
    Route::get('lead/list', [Lead::class, 'list'])->name('lead.list');
    Route::get('get/lead/details', [Lead::class, 'get_lead_details'])->name('get.lead.details');
    Route::post('update/lead/status', [Lead::class, 'update_lead_status'])->name('update.lead.status');

    //lead status
    Route::get('lead-status', [LeadStatus::class, 'index'])->name('lead.status');
    Route::post('lead-status/save', [LeadStatus::class, 'storeOrUpdate'])->name('leadstatus.store');
    Route::get('lead-status/list', [LeadStatus::class, 'getList'])->name('leadstatus.list');
    Route::get('lead-status/edit/{id}', [LeadStatus::class, 'edit'])->name('leadstatus.edit');
    Route::post('lead-status/delete', [LeadStatus::class, 'destroy'])->name('leadstatus.destroy');

    // Lead Source
    Route::get('lead-source', [LeadSource::class, 'index'])->name('lead.source');
    Route::post('lead-source/save', [LeadSource::class, 'storeOrUpdate'])->name('leadsource.store');
    Route::get('lead-source/list', [LeadSource::class, 'getList'])->name('leadsource.list');
    Route::get('lead-source/edit/{id}', [LeadSource::class, 'edit'])->name('leadsource.edit');
    Route::post('lead-source/delete', [LeadSource::class, 'destroy'])->name('leadsource.destroy');

    // Services
    Route::get('service', [Service::class, 'index'])->name('service');
    Route::post('service/save', [Service::class, 'storeOrUpdate'])->name('service.store');
    Route::get('service/list', [Service::class, 'getList'])->name('service.list');
    Route::get('service/edit/{id}', [Service::class, 'edit'])->name('service.edit');
    Route::post('service/delete', [Service::class, 'destroy'])->name('service.destroy');



    // Lead Details By MT
    Route::get('lead/details/{id}', [Lead::class, 'lead_details_index'])->name('lead.details');
    Route::post('lead/notes/store', [Lead::class, 'store_note'])->name('lead.notes.store');
    Route::get('lead/notes/get', [Lead::class, 'get_lead_notes'])->name('lead.notes.get');
    Route::post('lead/notes/delete', [Lead::class, 'delete_note'])->name('lead.notes.delete');
    Route::post('lead/notes/update', [Lead::class, 'update_note'])->name('lead.notes.update');
    Route::post('update/lead/source/status', [Lead::class, 'update_source_status'])->name('update.lead.source.status');
    Route::post('lead/timeline/data', [Lead::class, 'lead_timeline_data'])->name('lead.timeline.data');
    Route::post('/lead/file-upload', [Lead::class, 'uploadLeadFile'])->name('lead.file.upload');
    Route::get('/files/lead', [Lead::class, 'getLeadFiles'])->name('files.lead');
    Route::post('/files/lead/delete', [Lead::class, 'deleteLeadFile'])->name('files.lead.delete');

    // Deal By MT
    Route::get('deal', [Deal::class, 'index'])->name('deal')->middleware([CheckStaffPermission::class]);
    Route::get('deals/kanban-data', [Deal::class, 'get_kanban_data'])->name('deals.kanban-data');
    Route::post('deals/{deal}/update-stage', [Deal::class, 'update_deal_stage'])->name('deals.update-stage');
    Route::get('deals/list-data', [Deal::class, 'get_list_data'])->name('deals.list-data');
    Route::get('deals/details/{id}', [Deal::class, 'get_deal_details'])->name('deals.details');

    Route::post('/deal/update-stage', [Deal::class, 'updateStage'])->name('deal.updateStage');


    // Customer By MT
    Route::get('customer', [Customer::class, 'index'])->name('customer.index');
    Route::get('customer/list', [Customer::class, 'list'])->name('customer.list');
    Route::get('customer/store', [Customer::class, 'list'])->name('customer.store');
    Route::get('/customers/details/{customer_id}', [Customer::class, 'customer_data'])->name('customer.details');
    // Route::post('/customers/{id}', [Customer::class, 'update'])->name('customer.update');
    Route::post('/customers/update', [Customer::class, 'update'])->name('customer.update');

    //deal by MK
    // routes/web.php or routes/api.php depending on usage
    Route::post('/deals/closed-won', [Deal::class, 'closedWon'])->name('deals.closed-won');
    Route::post('/deals/closed-lost', [Deal::class, 'closedLost'])->name('deals.closed-lost');
    Route::post('deal/timeline/data', [Deal::class, 'deal_timeline_data'])->name('deal.timeline.data');

    Route::get('/get-deal-details/{lead_id}', [Deal::class, 'getDealclosedwonDetails'])->name('closed_won_details');

    // Route::post('/lead/update-source-status', [Deal::class, 'updateSourceStatus'])
    //     ->name('update.deallead.source.status'); //reomve its controller

    // Route::post('/lead/file-upload', [Deal::class, 'uploadLeadFile'])->name('lead.file.upload');
    // Route::get('/files/lead', [Deal::class, 'getLeadFiles'])->name('files.lead');
    // Route::post('/files/lead/delete', [Deal::class, 'deleteLeadFile'])->name('files.lead.delete');

   


Route::get('/countries/all', [LocationController::class, 'getCountries'])->name('countries.all');
Route::get('/states/{countryId}', [LocationController::class, 'getStates'])->name('states.by_country');
Route::get('/cities/{stateId}', [LocationController::class, 'getCities'])->name('cities.by_state');

    // Quotation By MT
    Route::get('get-deal-info/{id}', [Quotation::class, 'get_deal_info'])->name('deal.get.info');
    Route::get('get/products', [Quotation::class, 'get_products'])->name('get.products');
    Route::post('quotation/store', [Quotation::class, 'store'])->name('quotation.store');
    Route::get('/get-gst-by-hsn', [Quotation::class, 'getGstByHsn'])->name('get.gst.by.hsn');


    //callmodaldata by mk
    Route::post('deal/store', [Deal::class, 'store'])->name('deal.store');
    Route::post('deal/dealschedule/store', [Deal::class, 'calldealschedule'])->name('calldealschedule.store');
    Route::get('deals/calls/fetch', [Deal::class, 'dealcallfetch'])->name('dealscalls.fetch');
    Route::post('deal/call-schedule/fetch', [Deal::class, 'fetchdealcalldata'])->name('dealcall-schedule.fetch');
    Route::post('deal/call-log/fetch', [Deal::class, 'dealfetchCallLog'])->name('dealcall-log.fetch');
    Route::post('deal/call-schedule/delete', [Deal::class, 'dealdelete'])->name('dealcall-schedule.delete');
    Route::post('deal/lead/save-call-outcome', [Deal::class, 'dealsave_call_outcome'])->name('deallead.save.call.outcome');
    Route::post('deal/lead/reschedule-call', [Deal::class, 'dealrescheduleCall'])->name('lead.dealreschedule.call');
    Route::get('deal/get-deallead-info', [Deal::class, 'getDealLeadinfo'])->name('getdealleadinfo');
    Route::post('deal/cancel-call', [Deal::class, 'dealcancelCall'])->name('dealcancel.call');
    Route::post('deal/notes/store', [Deal::class, 'deal_store_note'])->name('deal.notes.store');
    Route::get('deal/notes/get', [Deal::class, 'get_deal_notes'])->name('deal.notes.get');
    Route::post('deal/notes/update', [Deal::class, 'deal_update_note'])->name('deal.notes.update');
    Route::post('deal/notes/delete', [Deal::class, 'deal_delete_note'])->name('deal.notes.delete');


    //callmodaldata
    Route::get('get-lead-info', [Lead::class, 'getLeadinfo'])->name('getleadinfo');
    Route::post('callschedule/store', [Lead::class, 'callschedule'])->name('callschedule.store');
    Route::get('calls/fetch', [Lead::class, 'fetch'])->name('calls.fetch');
    Route::post('call-schedule/delete', [Lead::class, 'delete'])->name('call-schedule.delete');
    Route::post('call-schedule/fetch', [Lead::class, 'fetchcalldata'])->name('call-schedule.fetch');
    Route::post('lead/save-call-outcome', [Lead::class, 'save_call_outcome'])->name('lead.save.call.outcome');
    Route::post('/lead/reschedule-call', [Lead::class, 'rescheduleCall'])->name('lead.reschedule.call');
    Route::post('/cancel-call', [Lead::class, 'cancelCall'])->name('cancel.call');
    Route::post('/call-log/fetch', [Lead::class, 'fetchCallLog'])->name('call-log.fetch');




    //add new for forget request
    Route::get('/forgot/timeout/requests', [Superadmin::class, 'showForgotTimeoutRequests'])->name('forgot.timeout.requests');
    Route::post('/superadmin/forgot-timeout-approve', [Superadmin::class, 'approveForgotTimeout'])->name('superadmin.forgot_timeout.update');
    Route::post('/superadmin/forgot-timeout-reject', [Superadmin::class, 'rejectForgotTimeout'])->name('superadmin.forgot_timeout.reject');
    Route::get('/forget-timeout-list', [Superadmin::class, 'getForgetTimeoutList'])->name('ForgetTimeoutList');

    //add leave view admin
    Route::get('leave', [Superadmin::class, 'index12'])->name('leave.index');

    Route::get('/superadmin/leave/list', [Superadmin::class, 'listLeaveRequests'])->name('superadmin.leave.list');
    Route::post('/superadmin/leave/approve', [Superadmin::class, 'approveLeave'])->name('leave.approve');
    Route::post('/superadmin/leave/reject', [Superadmin::class, 'rejectLeave'])->name('leave.reject');

    //leave allocation
    Route::get('leavebalance', [Superadmin::class, 'index13'])->name('leave.balance');
    Route::get('/superadmin/leavebalance/list', [Superadmin::class, 'leaveBalanceList'])->name('superadmin.leavebalance.list');
    Route::post('superadmin/leavebalance/update', [Superadmin::class, 'updateBalance'])->name('superadmin.leavebalance.update');

    //attendance view
    Route::get('attendancelist', [Superadmin::class, 'attendanceview'])->name('attendance.view');
    Route::get('/attendancelist/data', [Superadmin::class, 'data'])->name('attendance.data');
    Route::get('/attendancelist/detail', [Superadmin::class, 'getAttendanceDetail'])->name('attendance.get.detail');
   
    Route::get('showledgerreportlist', [Superadmin::class, 'showledgerreport'])->name('showledgerreport.view');
    
    Route::get('showledgerreport/data', [Superadmin::class, 'getLedgerDataJson'])->name('showledgerreport.data');

    Route::get('detailedattendancereport', [Superadmin::class, 'showDetailedAttendanceReport'])->name('showDetailedAttendanceReport.view');
Route::get('detailedattendancereport/data', [Superadmin::class, 'getDetailedAttendanceData'])->name('showDetailedAttendanceReport.data');
    
    //salary view

    Route::get('/salary', [Salary::class, 'attendanceview'])->name('salary.index');
    Route::post('/salary/calculate', [Salary::class, 'calculate'])->name('salary.calculate');
    Route::get('/salary/get-staff-salary', [Salary::class, 'getStaffSalary'])->name('salary.getStaffSalary');
    Route::get('/salary-data', [Salary::class, 'getSalaryData'])->name('salary.data');
    Route::get('/salary/slip', [Salary::class, 'getSalarySlip'])->name('salary.slip');
    Route::get('/salary/slip/download', [Salary::class, 'downloadSlip'])->name('salary.slip.download');
    Route::get('/salary/slip/preview-download', [Salary::class, 'downloadPreviewSlip'])->name('salary.slip.preview.download');





    // Lead By Quotation
    Route::get('quotation', [Quotation::class, 'index'])->name('quotation')->middleware([CheckStaffPermission::class]);
    Route::get('quotation/list', [Quotation::class, 'list'])->name('quotation.list');
    Route::get('quotation/edit', [Quotation::class, 'list'])->name('quotations.edit');
    Route::get('quotations/revised/{id}', [Quotation::class, 'quotation_revised'])->name('quotations.revised');
    Route::post('quotations/send-email', [Quotation::class, 'send_email_direct'])->name('quotations.sendEmail');


    Route::get('/quotation/{id}', [Quotation::class, 'show'])->name('quotation.show');


    // Company Documents By MT
    Route::get('documents', [Documents::class, 'index'])->name('documents')->middleware([CheckStaffPermission::class]);
    Route::post('document/category/store', [Documents::class, 'category_store'])->name('document.category.store');
    Route::get('document/category/get', [Documents::class, 'get_document_category'])->name('document.category.get');
    Route::get('document/category/update', [Documents::class, 'category_update'])->name('document.category.update');
    Route::post('document/category/delete', [Documents::class, 'category_delete'])->name('document.category.delete');
    Route::get('document/list/{id}', [Documents::class, 'document_list'])->name('document.list');
    Route::post('save/document', [Documents::class, 'save_document'])->name('save.document');
    Route::get('documents/list/category', [Documents::class, 'documents_list_category_wise'])->name('documents.list.category');
    Route::get('get/document/edit', [Documents::class, 'get_document_edit'])->name('get.document.edit');
    Route::get('get/document/history', [Documents::class, 'get_document_history'])->name('get.document.history');

    Route::get('documents/expiring', [Get_data::class, 'get_expiring_documents'])->name('documents.expiring');

    Route::get('profile', [Company_setting::class, 'index'])->name('profile');

    Route::get('rawmaterial', [Raw_material::class, 'index'])->name('rawmaterial')->middleware([CheckStaffPermission::class]);
    Route::get('getstockcategories', [Raw_material::class, 'get_stock'])->name('getstockcategories');
    Route::post('RawMaterialStore', [Raw_material::class, 'store'])->name('RawMaterialStore');
    Route::get('rawdata', [Raw_material::class, 'list'])->name('rawdata');

    //country
    Route::get('country', [Country::class, 'index'])->name('country')->middleware([CheckStaffPermission::class]);
    //Route::get('country', [CountryController::class, 'index'])->name('country');



    Route::get('CountryList', [Country::class, 'list'])->name('CountryList');


    Route::post('countryStore', [Country::class, 'store'])->name('countryStore');
    Route::post('country/delete', [Country::class, 'destroy'])->name('countryDelete');
    Route::get('country/edit/{id}', [Country::class, 'edit'])->name('country.edit');
    Route::post('country/update', [Country::class, 'update'])->name('country.update');



    // state
    Route::get('state', [State::class, 'index'])->name('state')->middleware([CheckStaffPermission::class]);
    Route::get('state.list', [State::class, 'getStates'])->name('state.list');
    Route::post('state/store', [State::class, 'storeOrUpdateStates'])->name('state.store');
    Route::get('state/edit/{id}', [State::class, 'edit'])->name('state.edit');
    Route::post('state/destroy/{id}', [State::class, 'destroy'])->name('state.destroy');

    Route::post('state/deleteByCountry/{id}', [State::class, 'deleteByCountry'])->name('state.deleteByCountry');


    // city
    Route::get('city', [City::class, 'index'])->name('city')->middleware([CheckStaffPermission::class]);
    Route::get('city/list', [City::class, 'getCities'])->name('city.list');
    Route::post('/city/store', [City::class, 'storeOrUpdateCities'])->name('city.store');
    Route::get('city/edit', [City::class, 'edit'])->name('city.edit');
    Route::post('city/destroy/{id}', [City::class, 'destroy'])->name('city.destroy');
    Route::post('city/deleteByState/{id}', [City::class, 'deleteByState'])->name('city.deleteByState');
    Route::post('city/deleteByCountryAndState', [City::class, 'deleteByCountryAndState'])->name('city.deleteByCountryAndState');


    // unit
    Route::get('unit', [Unit::class, 'index'])->name('unit')->middleware([CheckStaffPermission::class]);
    Route::get('UnitList', [Unit::class, 'list'])->name('UnitList');
    Route::post('unitStore', [Unit::class, 'store'])->name('unitStore');
    Route::get('unit/edit/{id}', [Unit::class, 'edit'])->name('unit.edit');
    Route::post('unit/update', [Unit::class, 'update'])->name('unit.update');
    Route::post('unit/delete', [Unit::class, 'destroy'])->name('unitDelete');

    //alternate unit
    Route::get('altunit', [Altunit::class, 'index'])->name('altunit')->middleware([CheckStaffPermission::class]);
    Route::get('AltunitList', [Altunit::class, 'list'])->name('AltunitList');
    Route::post('AltunitStore', [Altunit::class, 'store'])->name('AltunitStore');
    Route::post('altunit/delete', [Altunit::class, 'destroy'])->name('altunitDelete');
    Route::get('altunit/edit/{id}', [Altunit::class, 'edit'])->name('altunit.edit');
    Route::post('altunit/update', [Altunit::class, 'update'])->name('altunit.update');

    // gst
    //Route::get('gst', [Gst::class, 'index'])->name('gst')->middleware([CheckStaffPermission::class]);
    //Route::get('GstList', [Gst::class, 'list'])->name('GstList');
    // Route::post('GstStore', [Gst::class, 'store'])->name('GstStore');
    // Route::post('gst/delete', [Gst::class, 'destroy'])->name('GstDelete');
    // Route::get('gst/edit/{id}', [Gst::class, 'edit'])->name('gst.edit');
    // Route::post('gst/update', [Gst::class, 'update'])->name('gst.update');

    //plan
    Route::get('plan', [PlanController::class, 'index'])->name('planview');
    Route::post('plan/save', [PlanController::class, 'storeOrUpdate'])->name('plan.store');
    Route::get('plan/list', [PlanController::class, 'getList'])->name('plan.list');
    Route::get('plan/edit/{id}', [PlanController::class, 'edit'])->name('plan.edit');
    Route::post('plan/delete', [PlanController::class, 'destroy'])->name('plan.destroy');

    //trainer
    Route::get('trainer', [TrainerController::class, 'index'])->name('trainerview');

Route::post('trainer/save', [TrainerController::class, 'storeOrUpdate']) ->name('trainer.store');
Route::get('trainer/list', [TrainerController::class, 'getList'])->name('trainer.list');
Route::get('trainer/edit/{id}', [TrainerController::class, 'edit'])->name('trainer.edit');
Route::post('trainer/delete', [TrainerController::class, 'destroy']) ->name('trainer.destroy');

//member
Route::get('member', [MemberController::class, 'index'])->name('memberview');
Route::post('member/save', [MemberController::class, 'storeOrUpdate'])->name('member.store');
Route::get('member/list', [MemberController::class, 'getList'])->name('member.list');
Route::get('member/edit/{id}', [MemberController::class, 'edit'])->name('member.edit');
Route::post('member/delete', [MemberController::class, 'destroy'])->name('member.destroy');

//subscription
Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscriptionview');
Route::post('subscription/save', [SubscriptionController::class, 'storeOrUpdate'])->name('subscription.store');
Route::get('subscription/list', [SubscriptionController::class, 'getList'])->name('subscription.list');
Route::get('subscription/edit/{id}', [SubscriptionController::class, 'edit'])->name('subscription.edit');
Route::post('subscription/delete', [SubscriptionController::class, 'destroy'])->name('subscription.destroy');
Route::get('subscription/plan-details/{id}', [SubscriptionController::class, 'planDetails'])->name('subscription.plandetails');

//payment 
Route::get('payment', [PaymentController::class, 'index'])->name('paymentview');
Route::post('payment/save', [PaymentController::class, 'storeOrUpdate'])->name('payment.store');
Route::get('payment/list', [PaymentController::class, 'getList'])->name('payment.list');
Route::get('payment/edit/{id}', [PaymentController::class, 'edit'])->name('payment.edit');
Route::post('payment/delete', [PaymentController::class, 'destroy'])->name('payment.destroy');
Route::get('payment/subscription-details/{id}', [PaymentController::class, 'subscriptionDetails'])->name('payment.subdetails');
Route::get('payment/receipt/{id}', [PaymentController::class, 'downloadReceipt'])->name('payment.receipt');

    //gst
    Route::get('gst', [Gst::class, 'index'])->name('gstview');
    Route::post('gst/save', [Gst::class, 'storeOrUpdate'])->name('gst.store');
    Route::get('gst/list', [Gst::class, 'getList'])->name('gst.list');
    Route::get('gst/edit/{id}', [Gst::class, 'edit'])->name('gst.edit');
    Route::post('gst/delete', [Gst::class, 'destroy'])->name('gst.destroy');

    //category
    Route::get('category', [Category::class, 'index'])->name('categoryview');
    Route::post('category/save', [Category::class, 'storeOrUpdate'])->name('category.store');
    Route::get('category/list', [Category::class, 'getList'])->name('category.list');
    Route::get('category/edit/{id}', [Category::class, 'edit'])->name('category.edit');
    Route::post('category/delete', [Category::class, 'destroy'])->name('category.destroy');

    ////deal-stage
    Route::get('dealstage', [DealstageController::class, 'index'])->name('dealstageview');
    Route::post('dealstage/save', [DealstageController::class, 'storeOrUpdate'])->name('dealstage.store');
    Route::get('dealstage/list', [DealstageController::class, 'getList'])->name('dealstage.list');
    Route::get('dealstage/edit/{id}', [DealstageController::class, 'edit'])->name('dealstage.edit');
    Route::post('dealstage/delete', [DealstageController::class, 'destroy'])->name('dealstage.destroy');

    //terms
    Route::get('terms', [Terms::class, 'index'])->name('terms');
    //Route::get('TermsList', [Terms::class, 'list'])->name('TermsList');
    Route::post('TermsStore', [Terms::class, 'store'])->name('TermsStore');
    //Route::post('terms/delete', [Terms::class, 'destroy'])->name('TermsDelete');
    Route::get('terms/view', [Terms::class, 'show'])->name('terms.view'); // for fetching
    Route::get('terms/edit/{id}', [Terms::class, 'edit'])->name('terms.edit');
    Route::post('terms/update', [Terms::class, 'update'])->name('terms.update');

    //Dashboard
    Route::get('staff/dashboard', [Dashboard::class, 'dashboard'])->name('staff/dashboard');
    Route::get('/staff/attendance-summary', [Dashboard::class, 'getStaffAttendanceSummary'])
        ->name('staff.attendance.summary');
    Route::get('/staff/details', [Dashboard::class, 'viewDetails'])->name('staff.attendance.details');
    Route::post('/staff/forgot-timeout-request', [Dashboard::class, 'submitForgotTimeout'])->name('staff.forgot_timeout.request');

    //leave
    Route::get('staff/leave', [Leave::class, 'index'])->name('staff.leave')->middleware([CheckStaffPermission::class]);
    Route::post('/leave/store', [Leave::class, 'store'])->name('leave.store');
    Route::post('leave/update', [Leave::class, 'update'])->name('leave.update');
    Route::get('leave-list', [Leave::class, 'list'])->name('leave.list');

    //mail-config
    Route::get('mail-config', [MailConfig::class, 'index'])->name('mail_config');
    Route::post('mail-config/update', [MailConfig::class, 'store'])->name('mail.config.update');



    Route::get('supplier', [Supplier::class, 'index'])->name('supplier')->middleware([CheckStaffPermission::class]);
    Route::post('SupplierStore', [Supplier::class, 'store'])->name('SupplierStore');
    Route::get('SupplierData', [Supplier::class, 'list'])->name('SupplierData');
    Route::get('getsupplierdata', [Supplier::class, 'get_supplier'])->name('getsupplierdata');

    Route::get('pogenerate', [Purchase::class, 'index'])->name('pogenerate')->middleware([CheckStaffPermission::class]);
    Route::post('POStore', [Purchase::class, 'store'])->name('POStore');
    Route::get('PoData', [Purchase::class, 'list'])->name('PoData');
    Route::get('generate-po-no', [Purchase::class, 'generatePoNo'])->name('generate-po-no');
    Route::get('ShowPoOrder', [Purchase::class, 'get_data'])->name('ShowPoOrder');
    Route::get('popdf/{po_id}', [Purchase::class, 'po_pdf'])->name('popdf');


    Route::get('poinward', [Purchase_inward::class, 'index'])->name('poinward')->middleware([CheckStaffPermission::class]);
    Route::get('getpoitems', [Purchase_inward::class, 'get_po_items'])->name('getpoitems');
    Route::post('POInwStore', [Purchase_inward::class, 'store'])->name('POInwStore');
    Route::get('PoInwData', [Purchase_inward::class, 'list'])->name('PoInwData');
    Route::get('ShowPoIwd', [Purchase_inward::class, 'get_data'])->name('ShowPoIwd');

    Route::get('getstates', [Supplier::class, 'getStatesByCountry'])->name('getstates');
    Route::get('getcities', [Supplier::class, 'getCitiesByState'])->name('getcities');

    Route::get('qtyset', [Qty_set::class, 'index'])->name('qtyset');
    Route::post('qtystore', [Qty_set::class, 'store'])->name('qtystore');


    Route::get('getreorderalert', [Company::class, 'getReorderAlerts'])->name('getreorderalert');
    Route::get('getworkorderalert', [Company::class, 'getWorkorderAlerts'])->name('getworkorderalert');
    Route::get('present-staff', [Company::class, 'getPresentStaffList'])->name('present.staff');
    

    Route::get('product', [Product::class, 'index'])->name('product')->middleware([CheckStaffPermission::class]);
    Route::post('ProductStore', [Product::class, 'store'])->name('ProductStore');
    Route::get('ProductData', [Product::class, 'list'])->name('ProductData');
    Route::get('/get-hsn-by-gst', [Product::class, 'getGstByHsn'])->name('get-hsn-by-gst');
    Route::get('ShowProduct', action: [Product::class, 'get_data'])->name('ShowProduct');

    Route::get('mstvendor', [Vendor::class, 'index'])->name('mstvendor')->middleware([CheckStaffPermission::class]);
    Route::post('VendorStore', [Vendor::class, 'store'])->name('VendorStore');
    Route::get('VendorData', [Vendor::class, 'list'])->name('VendorData');
    Route::get('getvendordata', [Vendor::class, 'get_vendor'])->name('getvendordata');

    // Product Mapping
    Route::get('productmapping', [Product_mapping::class, 'index'])->name('productmapping')->middleware([CheckStaffPermission::class]);
    Route::post('ProductRawStore', [Product_mapping::class, 'store'])->name('ProductRawStore');
    Route::get('ProductRawData', [Product_mapping::class, 'list'])->name('ProductRawData');
    // Work Order
    Route::get('workorder', [Work_order::class, 'index'])->name('workorder')->middleware([CheckStaffPermission::class]);
    Route::get('get-quotation-products/{id}', [Work_order::class, 'getQuotationProducts'])->name('get-quotation-products');
    Route::post('WoStore', [Work_order::class, 'store'])->name('WoStore');
    Route::get('WoData', [Work_order::class, 'list'])->name('WoData');
    Route::get('wopdf/{wo_id}', [Work_order::class, 'wo_pdf'])->name('wopdf');
    Route::get('wohistory/{wo_id}', [Work_order::class, 'wo_history'])->name('wohistory');
    Route::get('WorkHistoryData/{wo_id}', [Work_order::class, 'wo_history_data'])->name('WorkHistoryData');
    Route::get('ShowWoOrder', [Work_order::class, 'get_data'])->name('ShowWoOrder');
    Route::post('WorkApprove', [Work_order::class, 'work_order_approve'])->name('WorkApprove');
    Route::post('WorkReject', [Work_order::class, 'work_order_reject'])->name('WorkReject');

    // Material Issue
    Route::get('materialissue', [Material_issue::class, 'index'])->name('materialissue')->middleware([CheckStaffPermission::class]);
    Route::get('get-material-data/{id}/{woId}', [Material_issue::class, 'getMaterialData'])->name('get-material-data');
    Route::post('RawStore', [Material_issue::class, 'store'])->name('RawStore');
    Route::get('MaterialIssueData', [Material_issue::class, 'list'])->name('MaterialIssueData');
    Route::get('mipdf/{mi_id}', [Material_issue::class, 'mi_pdf'])->name('mipdf');


    Route::get('poreturn', [Purchase_return::class, 'index'])->name('poreturn')->middleware([CheckStaffPermission::class]);
    Route::get('getporeturnitems', [Purchase_return::class, 'po_items'])->name('getporeturnitems');
    Route::post('POReturnStore', [Purchase_return::class, 'store'])->name('POReturnStore');
    Route::get('PoReturnData', [Purchase_return::class, 'list'])->name('PoReturnData');
    Route::get('ShowPoReturn', [Purchase_return::class, 'get_data'])->name('ShowPoReturn');
    Route::get('ShowCredit', [Purchase_return::class, 'get_data_credit'])->name('ShowCredit');
    Route::post('CreditStore', [Purchase_return::class, 'credit_store'])->name('CreditStore');

    Route::get('creditlist', [Credit_report::class, 'index'])->name('creditlist')->middleware([CheckStaffPermission::class]);
    Route::get('CreditData', [Credit_report::class, 'list'])->name('CreditData');
    Route::get('creditpdf/{credit_id}', [Credit_report::class, 'credit_pdf'])->name('creditpdf');
    // Production
    Route::get('production', [Production::class, 'index'])->name('production')->middleware([CheckStaffPermission::class]);
    Route::get('get-workorder-materials/{id}', [Production::class, 'getWorkOrderMaterialIssues'])->name('get-workorder-materials');
    Route::get('get-workorder-products/{id}', [Production::class, 'getWorkOrderProducts'])->name('get-workorder-products');
    Route::get('ProductionListData', [Production::class, 'list'])->name('ProductionListData');
    Route::post('/store-production', [Production::class, 'store'])->name('ProductionStore');



    // after sales and service
    Route::get('complaintlogg', [Complaint::class, 'index'])->name('complaintlogg')->middleware([CheckStaffPermission::class]);
    Route::get('amctracking', [AMC_tracking::class, 'index'])->name('amctracking')->middleware([CheckStaffPermission::class]);
    Route::get('inventoryreport', [Inventory_report::class, 'index'])->name('inventoryreport')->middleware([CheckStaffPermission::class]);
    Route::get('InventoryData', [Inventory_report::class, 'list'])->name('InventoryData');
    Route::get('InventoryDownloadPdf', [Inventory_report::class, 'download_pdf'])->name('InventoryDownloadPdf');
    //company document
    Route::get('company/document', [CompanyDocument::class, 'index'])->name('company.document');
    Route::post('certificate/upload', [CompanyDocument::class, 'store_document'])->name('certificate.upload');
    Route::get('documents/grouped', [CompanyDocument::class, 'groupedDocuments'])->name('documents.grouped');
    Route::delete('documents/delete/{id}', [CompanyDocument::class, 'destroy'])->name('documents.destroy');
    // Route::post('/documents/delete', [CompanyDocument::class, 'delete'])->name('documents.delete');
    Route::get('documents/edit/{id}', [CompanyDocument::class, 'edit'])->name('documents.edit');
    Route::put('documents/{id}', [CompanyDocument::class, 'update_document'])->name('documents.update');

    //stock Group
    Route::get('stockgroup', [StockGroup::class, 'index'])->name('stockgroup');
    Route::post('stock-group', [StockGroup::class, 'storeOrUpdate'])->name('stockgroup.store');
    Route::get('stockgroup/list', [StockGroup::class, 'getStockGroups'])->name('stockgroup.list');
    Route::get('stockgroup/edit/{id}', [StockGroup::class, 'edit'])->name('stockgroup.edit');
    // Route::post('stockgroup/destroy{id}', [StockGroup::class, 'destroy'])->name('stockgroup.destroy');
    Route::post('stockgroup/destroy', [StockGroup::class, 'destroy'])->name('stockgroup.destroy');
    //stock category
    Route::get('stockCategory', [StockCategory::class, 'index'])->name('stockCategory');
    Route::post('stockcategory/store', [StockCategory::class, 'storeOrUpdate'])->name('stockcategory.store');
    Route::get('stockcategory.list', [StockCategory::class, 'getStockCategories'])->name('stockcategory.list');
    Route::get('stockcategory/edit/{id}', [StockCategory::class, 'edit'])->name('stockcategory.edit');
    Route::post('stockcategory/destroy{id}', [StockCategory::class, 'destroy'])->name('stockcategory.destroy');
    Route::post('stockcategory/delete{sg_id}', [StockCategory::class, 'delete'])->name('stockcategory.delete');

    //product Category
    Route::get('productcategory', [Product_category::class, 'index'])->name('productcategory')->middleware([CheckStaffPermission::class]);
    Route::post('ProductCatStore', [Product_category::class, 'store'])->name('ProductCatStore');
    Route::get('procatdata', [Product_category::class, 'list'])->name('procatdata');
    Route::get('getproductcatdata', [Product_category::class, 'get_data'])->name('getproductcatdata');

    //HSN GST Mapping
    Route::get('hsn/gst/mapping', [Hsn_Gst::class, 'index'])->name('hsn.gst.mapping')->middleware([CheckStaffPermission::class]);
    Route::post('/hsn-gst', [Hsn_Gst::class, 'store'])->name('hsn-gst.store');
    Route::get('hsn/list', [Hsn_Gst::class, 'list'])->name('hsn.list');
    Route::post('/hsn/delete', [Hsn_Gst::class, 'destroy'])->name('hsn.delete');


    Route::get('currency', [Currency::class, 'index'])->name('currency')->middleware([CheckStaffPermission::class]);

    Route::get('/currency/getData', [Currency::class, 'getData'])->name('currency.getData');
    Route::post('/currency/store', [Currency::class, 'store'])->name('currency.store');
    Route::get('/currency/edit', [Currency::class, 'edit'])->name('currency.edit');
    Route::post('/currency/update', [Currency::class, 'update'])->name('currency.update');
    Route::post('/currency/delete', [Currency::class, 'delete'])->name('currency.delete');
    Route::post('/currency/refresh-rate', [Currency::class, 'refreshRate'])->name('currency.refreshRate');

    Route::get('changepassword', [Login::class, 'change_password'])->name('changepassword');
    Route::post('UpdatePass', [Login::class, 'update_password'])->name('UpdatePass');

    // expense type
    Route::get('expensetype', [Expense_type::class, 'index'])->name('expensetype')->middleware([CheckStaffPermission::class]);
    Route::post('ExpenseTypeStore', [Expense_type::class, 'store'])->name('ExpenseTypeStore');
    Route::get('expensetypedata', [Expense_type::class, 'list'])->name('expensetypedata');
    Route::get('getexpensetypedata', [Expense_type::class, 'get_data'])->name('getexpensetypedata');

    Route::get('bank', [Bank::class, 'index'])->name('bank')->middleware([CheckStaffPermission::class]);
    Route::post('BankStore', [Bank::class, 'store'])->name('BankStore');
    Route::get('bankData', [Bank::class, 'list'])->name('bankData');
    Route::get('getbankdata', [Bank::class, 'get_data'])->name('getbankdata');

    Route::get('dispatch', [Dispatch::class, 'index'])->name('dispatch')->middleware([CheckStaffPermission::class]);
    Route::post('DispatchStore', [Dispatch::class, 'store'])->name('DispatchStore');
    Route::get('dispatchlist', [Dispatch::class, 'list'])->name('DispatchData');
    Route::get('get-production-products/{id}', [Dispatch::class, 'getProductionProducts'])->name('get-production-products');
    Route::get('invoicepdf/{dispatch_id}', [Dispatch::class, 'invoice_pdf'])->name('invoicepdf');
    Route::get('invoice', [Invoice::class, 'index'])->name('invoice')->middleware([CheckStaffPermission::class]);
    Route::post('/dispatch/update-status', [Dispatch::class, 'updateDispatchStatus'])->name('DispatchUpdateStatus');
    Route::post('/production/update-status', [Production::class, 'updateStatus'])->name('ProductionUpdateStatus');

    Route::get('internalqc', [Internal::class, 'index'])->name('internalqc')->middleware([CheckStaffPermission::class]);
    Route::get('InternalListData', [Internal::class, 'list'])->name('InternalListData');
    Route::post('InternalAccept', [Internal::class, 'accept'])->name('InternalAccept');
    Route::post('InternalReject', [Internal::class, 'reject'])->name('InternalReject');
    Route::get('ShowInternal', [Internal::class, 'get_data'])->name('ShowInternal');
    Route::post('InternalAccRejStore', [Internal::class, 'store'])->name('InternalAccRejStore');

    Route::get('finalqc', [Final_qc::class, 'index'])->name('finalqc')->middleware([CheckStaffPermission::class]);
    Route::get('FinalListData', [Final_qc::class, 'list'])->name('FinalListData');
    Route::post('FinalAccept', [Final_qc::class, 'accept'])->name('FinalAccept');
    Route::post('FinalReject', [Final_qc::class, 'reject'])->name('FinalReject');
    Route::get('ShowFinal', [Final_qc::class, 'get_data'])->name('ShowFinal');
    Route::post('FinalAccRejStore', [Final_qc::class, 'store'])->name('FinalAccRejStore');
      
    Route::get('scrap', [Scrap::class, 'index'])->name('scrap')->middleware([CheckStaffPermission::class]);
    Route::get('ScrapListData', [Scrap::class, 'list'])->name('ScrapListData');

    Route::get('newworkorder/{wo_id}/{production_id}', [Work_order::class, 'rejected_work_order'])->name('newworkorder')->middleware([CheckStaffPermission::class]);
    Route::get('get-quotation-products_rejected/{id}/{production_id}', [Work_order::class, 'getQuotationProductsRejected'])->name('get-quotation-products_rejected');
    Route::get('get-work-orders/{customer_id}', [Work_order::class, 'getWorkOrders'])->name('get-work-orders');
    Route::post('/get-workorders-due-dates', [Work_order::class, 'checkDueDates'])->name('get-workorders-due-dates');
});


Route::get('attendance', [Attendance::class, 'index'])->name('attendance');
Route::post('attendance/mark', [Attendance::class, 'markAttendance'])->name('attendance.mark');
Route::get('attendance/count', [Attendance::class, 'getRegisteredCount'])->name('attendance.count');
Route::put('attendance/forgot-out-request', [Attendance::class, 'submitForgotOutRequest'])->name('attendance.submitForgotOutRequest');
Route::get('detection', function () {
    return view('company/master/staff/detection');
});
Route::get('quotations/pdf/{id}', [Quotation::class, 'quotation_pdf'])->name('quotations.pdf');

Route::get('sample/pdf', [Quotation::class, 'pdf_open'])->name('sample.pdf');

Route::get('/call-reminder-view', function () {
    return view('pdf_sample');
});


//by mk
Route::get('/open-lead/list', [OpenLead::class, 'openLeadList'])->name('openlead.list');
Route::get('/get-company-names', [Lead::class, 'getCompanyNames'])->name('get.company.names');




// Route::get('/check-and-push-calls', function () {
//     $now = now();
//     $today = $now->toDateString();
//     $currentTime = $now->format('H:i');

//     $calls = DB::table('tbl_call_schedule')
//         ->whereDate('call_date', $today)
//         ->whereTime('call_time', $currentTime)
//         ->get();


//     if ($calls->isEmpty()) {
//         return 'No scheduled calls right now.';
//     }

//     $pusher = new Pusher\Pusher(
//         env('PUSHER_APP_KEY'),
//         env('PUSHER_APP_SECRET'),
//         env('PUSHER_APP_ID'),
//         [
//             'cluster' => env('PUSHER_APP_CLUSTER'),
//             'useTLS' => true
//         ]
//     );

//     foreach ($calls as $call) {
//         $pusher->trigger('deal-call', 'call.reminder', [
//             'lead_name' => $call->lead_name ?? '',
//             'email' => $call->email ?? '',
//             'phone' => $call->phone ?? '',
//             'company' => $call->company ?? '',
//             'message' => "It's time to call {$call->lead_name}!"
//         ]);
//     }

//     return 'Call notifications sent.';
// });
