<?php

namespace App\Http\Controllers;

use App\Models\CompanyModel;
use App\Models\Company_submenu;
use App\Models\Company_menu;
use App\Models\RawmaterialModel;
use App\Models\SupplierModel;
use App\Models\Purchase_order_Model;
use App\Models\Purchase_inward_Model;
use App\Models\ProductModel;
use App\Models\VendorModel;
use App\Models\ProductRawMappingModel;
use App\Models\WoMaterialModel;
use App\Models\CountryModel;
use App\Models\UnitModel;
use App\Models\AltUnitModel;
use App\Models\GstModel;
use App\Models\ProductCategoryModel;
use App\Models\ExpenseTypeModel;
use App\Models\BankModel;
use App\Models\Attendancemodel;
use App\Models\Staffmodel;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class Company extends Controller
{
    protected $actual_path;
    public function __construct()
    {
        $this->actual_path = config('app.actual_url') . '/uploads/';
    }
    public function index()
    {
        $page_title = 'Companies Register';
        $page_name = 'Companies Register';
        $menus = DB::table('company_menus')
            ->where('status', 0)
            ->orderBy('menu_order', 'asc')
            ->get()
            ->map(function ($menu) {
                if ($menu->is_submenu) {
                    $menu->submenus = DB::table('company_submenus')
                        ->where('c_menu_id', $menu->c_menu_id)
                        ->where('status', 0)
                        ->orderBy('submenu_order', 'asc')
                        ->get();
                } else {
                    $menu->submenus = collect([]);
                }
                return $menu;
            });
        return view('superadmin/company_register', compact('page_title', 'page_name', 'menus'));
    }
    public function dashboard()
    {
         $today = Carbon::today()->toDateString();
        $page_title = 'Company Dashboard';
        $page_name = 'Company Dashboard';
        return view('company/dashboard', compact('page_title', 'page_name','today'));
    }
    public function destroy(Request $request, $id)
    {
        $module = $request->input('module');

        switch ($module) {
            case 'raw_material':
                $updated = RawmaterialModel::where('rm_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Raw material deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Raw material not found or already deleted.'], 404);
                }

            case 'supplier':
                $updated = SupplierModel::where('supplier_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Supplier deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Supplier not found or already deleted.'], 404);
                }

            case 'purchase_order':
                $updated = Purchase_order_Model::where('po_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Purchase Order deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Purchase Order not found or already deleted.'], 404);
                }

            case 'purchase_inward':
                $updated = Purchase_inward_Model::where('pi_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Purchase Inward deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Purchase Inward not found or already deleted.'], 404);
                }

            case 'product':
                $updated = ProductModel::where('product_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Product deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Product not found or already deleted.'], 404);
                }

            case 'vendor':
                $updated = VendorModel::where('vendor_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Vendor deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Vendor not found or already deleted.'], 404);
                }

            case 'product_map':
                $updated = ProductRawMappingModel::where('product_raw_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Product Raw Mapping deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Product Raw Mapping not found or already deleted.'], 404);
                }

            case 'country':
                $updated = CountryModel::where('c_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Country deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Country not found or already deleted.'], 404);
                }

            case 'unit':
                $updated = UnitModel::where('unit_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Unit deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Unit not found or already deleted.'], 404);
                }
            case 'altunit':
                $updated = AltUnitModel::where('alt_unit_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Alternative Unit deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Alternative Unit not found or already deleted.'], 404);
                }
            case 'gst':
                $updated = GstModel::where('gst_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Gst No deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Gst Nos not found or already deleted.'], 404);
                }

            case 'product_category':
                $updated = ProductCategoryModel::where('category_id', $id)->update(['status' => 1]);

                if ($updated) {
                    return response()->json(['message' => 'Product Category deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Product Category not found or already deleted.'], 404);
                }

            case 'expense_type':
                $updated = ExpenseTypeModel::where('expense_type_id', $id)->update(['status' => 1]);
                if ($updated) {
                    return response()->json(['message' => 'Expense Type deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Expense Type not found or already deleted.'], 404);
                }

            case 'bank':
                $updated = BankModel::where('bank_id', $id)->update(['status' => 1]);
                if ($updated) {
                    return response()->json(['message' => 'Bank deleted successfully.']);
                } else {
                    return response()->json(['message' => 'Bank not found or already deleted.'], 404);
                }

            default:
                return response()->json(['message' => 'Invalid module.'], 400);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $rules = [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'contact_no' => 'nullable|string|max:20',
            'project_name' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,ico',
            'registration_number' => 'nullable|string|max:50',
            'company_type' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:50',
            'founded_date' => 'nullable|date',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:100',
            'tax_id' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'menus' => 'nullable|array',
            'menus.*' => 'integer|exists:company_menus,c_menu_id',
            'submenus' => 'nullable|array',
            'submenus.*' => 'integer|exists:company_submenus,c_sub_menu_id',
        ];

        if (!$request->has('company_id')) {
            $rules['email'] .= '|unique:companies,email';
            $rules['registration_number'] .= '|unique:companies,registration_number';
        } else {
            $rules['email'] .= '|unique:companies,email,' . $request->company_id . ',company_id';
            $rules['registration_number'] .= '|unique:companies,registration_number,' . $request->company_id . ',company_id';
        }

        $validated = $request->validate($rules);

        // if ($request->hasFile('logo')) {
        //     $uploadPath = public_path('assets/uploads/company');
        //     if (!file_exists($uploadPath)) {
        //         mkdir($uploadPath, 0755, true);
        //     }
        //     $extension = $request->file('logo')->getClientOriginalExtension();
        //     $fileName = time() . '_logo.' . $extension;
        //     $request->file('logo')->move($uploadPath, $fileName);
        //     $validated['logo'] = $fileName;
        // }

        // if ($request->hasFile('favicon')) {
        //     $uploadPath = public_path('assets/uploads/company');
        //     if (!file_exists($uploadPath)) {
        //         mkdir($uploadPath, 0755, true);
        //     }
        //     $extension = $request->file('favicon')->getClientOriginalExtension();
        //     $fileName = time() . '_favicon.' . $extension;
        //     $request->file('favicon')->move($uploadPath, $fileName);
        //     $validated['favicon'] = $fileName;
        // }

        if ($request->hasFile('logo')) {
            $uploadPath = public_path('assets/uploads/company');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if ($request->has('company_id') && $request->company_id != null) {
                $company = CompanyModel::find($request->company_id);
                if ($company && $company->logo) {
                    $oldLogoPath = $uploadPath . '/' . $company->logo;
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }
            }

            $extension = $request->file('logo')->getClientOriginalExtension();
            $fileName = time() . '_logo.' . $extension;
            $request->file('logo')->move($uploadPath, $fileName);
            $validated['logo'] = $fileName;
        }

        if ($request->hasFile('favicon')) {
            $uploadPath = public_path('assets/uploads/company');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if ($request->has('company_id') && $request->company_id != null) {
                $company = CompanyModel::find($request->company_id);
                if ($company && $company->favicon) {
                    $oldFaviconPath = $uploadPath . '/' . $company->favicon;
                    if (file_exists($oldFaviconPath)) {
                        unlink($oldFaviconPath);
                    }
                }
            }

            $extension = $request->file('favicon')->getClientOriginalExtension();
            $fileName = time() . '_favicon.' . $extension;
            $request->file('favicon')->move($uploadPath, $fileName);
            $validated['favicon'] = $fileName;
        }


        if ($request->has('company_id') && $request->company_id != null) {
            $company = CompanyModel::find($request->company_id);
            if (!$company) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found.'
                ], 404);
            }

            $company->update($validated);
            DB::table('company_permission')->where('company_id', $company->company_id)->delete();
        } else {
            $validated['created_by'] = 0;
            $validated['password'] = md5(123456);
            $validated['status'] = 0;

            $company = CompanyModel::create($validated);
        }

        if ($company) {
            if ($request->has('menus') || $request->has('submenus')) {
                $menuPermissions = [];
                $now = now();

                if ($request->has('menus')) {
                    foreach ($request->menus as $menuId) {
                        $menuPermissions[] = [
                            'company_id' => $company->company_id,
                            'c_menu_id' => $menuId,
                            'c_sub_menu_id' => null,
                            'status' => 0,
                            'created_by' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($request->has('submenus')) {
                    foreach ($request->submenus as $submenuId) {
                        $submenu = DB::table('company_submenus')->where('c_sub_menu_id', $submenuId)->first();
                        if ($submenu) {
                            $menuPermissions[] = [
                                'company_id' => $company->company_id,
                                'c_menu_id' => $submenu->c_menu_id,
                                'c_sub_menu_id' => $submenuId,
                                'status' => 0,
                                'created_by' => 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }

                if (!empty($menuPermissions)) {
                    DB::table('company_permission')->insert($menuPermissions);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->has('company_id') && $request->company_id != null ? 'Company updated successfully' : 'Company created successfully',
            ]);
        } else {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create or update company.'
            ], 500);
        }
    }

    public function list()
    {
        $companies = CompanyModel::select(['company_id', 'logo', 'name', 'email', 'contact_no', 'company_type', 'status']);
        return DataTables::of($companies)
            ->addIndexColumn()
            ->addColumn('logo', function ($company) {
                $logo = $company->logo ? $this->actual_path . '/company/' . $company->logo : $this->actual_path . '/company.png';
                return '<img src="' . $logo . '" alt="Logo" style="max-height:50px; max-width:100px;">';
            })
            ->addColumn('action', function ($company) {
                return '
            <div class="btn-group">
                <button class="btn btn-sm btn-primary edit-btn" data-id="' . $company->company_id . '">Edit</button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="' . $company->company_id . '">Delete</button>
            </div>
            ';
            })
            ->editColumn('status', function ($company) {
                return $company->status
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->rawColumns(['logo', 'action', 'status'])
            ->make(true);
    }
    public function edit($id)
    {

        // dd($id);
        $company = CompanyModel::with(['permissions' => function ($query) {
            $query->select('company_id', 'c_menu_id', 'c_sub_menu_id');
        }])->findOrFail($id);
        $menus = [];
        $submenus = [];
        foreach ($company->permissions as $permission) {
            if ($permission->c_sub_menu_id) {
                $submenus[] = $permission->c_sub_menu_id;
            } else {
                $menus[] = $permission->c_menu_id;
            }
        }
        $company->menus = $menus;
        $company->submenus = $submenus;
        return response()->json($company);
    }
    // In your Company controller
    public function getCountries(Request $request)
    {
        $search = $request->search;
        $page = $request->page ?? 1;
        $fetchOne = $request->fetch_one ?? false;
        $perPage = 10;

        $query = DB::table('mst_country')
            ->select('c_id as id', 'country_name as text');

        if ($fetchOne && $search) {

            return $query->where('c_id', $search)->first();
        }

        if ($search) {
            $query->where('country_name', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'results' => $results,
            'total_count' => $total
        ]);
    }

    public function getStates(Request $request)
    {
        $search = $request->search;
        $page = $request->page ?? 1;
        $countryId = $request->country_id;
        $fetchOne = $request->fetch_one ?? false;
        $perPage = 10;

        $query = DB::table('mst_state')
            ->select('state_id as id', 'state_name as text')
            ->where('c_id', $countryId);

        if ($fetchOne && $search) {

            return $query->where('state_id', $search)->first();
        }

        if ($search) {
            $query->where('state_name', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'results' => $results,
            'total_count' => $total
        ]);
    }

    public function getCities(Request $request)
    {
        $search = $request->search;
        $page = $request->page ?? 1;
        $stateId = $request->state_id;
        $fetchOne = $request->fetch_one ?? false;
        $perPage = 10;

        $query = DB::table('mst_city')
            ->select('city_id as id', 'city_name as text')
            ->where('state_id', $stateId);

        if ($fetchOne && $search) {

            return $query->where('city_id', $search)->first();
        }

        if ($search) {
            $query->where('city_name', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'results' => $results,
            'total_count' => $total
        ]);
    }

    public function getReorderAlerts()
    {

        $qtySet = DB::table('tbl_qty_set')->value('qty_set');
        if ($qtySet === null) {
            return response()->json(['success' => false, 'message' => 'Reorder threshold not set.']);
        }
        $alerts = DB::table('tbl_po_inventory as inv')
            ->join('tbl_raw_material as rm', 'inv.rm_id', '=', 'rm.rm_id')
            ->where('inv.avl_qty', '<', $qtySet)
            ->where("inv.avl_qty", '>', '0')
            ->select('rm.name as rm_name', 'inv.avl_qty')
            ->get();
        return response()->json([
            'success' => true,
            'qty_set' => $qtySet,
            'data' => $alerts
        ]);
    }

    public function getWorkorderAlerts()
    {
        $alerts = WoMaterialModel::select([
            'tbl_wo_raw_material.wo_id',
            'tbl_work_order.work_order_no',
            'tbl_raw_material.name as raw_material',
            DB::raw('SUM(tbl_wo_raw_material.unreserved_qty) as unreserved_qty')
        ])
            ->join('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_wo_raw_material.wo_id')
            ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_wo_raw_material.rm_id')
            ->whereNotNull('tbl_wo_raw_material.unreserved_qty')
            ->where('tbl_wo_raw_material.unreserved_qty', '>', 0)
            ->groupBy('tbl_wo_raw_material.wo_id', 'tbl_work_order.work_order_no', 'tbl_raw_material.name')
            ->OrderBy('tbl_raw_material.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $alerts
        ]);
    }
    public function getPresentStaffList(Request $request)
    {
        // Get the current date in the format used in your database (e.g., 'Y-m-d')
         $today = Carbon::today()->toDateString(); 

         
        
        // Fetch attendance records for today where time_in is recorded
        // and join with the Staffmodel to get staff name and face image.
        $presentStaff = Attendancemodel::whereDate('time_in', $today)
            ->whereNotNull('time_in')
            ->where(function($query) {
                // Filter out records that are explicit leaves, if applicable in your system
                $query->where('status', '!=', 'leave')->orWhereNull('status');
            })
            ->with('staff') // Eager load the staff relationship
            ->get();

        $data = $presentStaff->map(function ($attendance) use ($today) {

              $faceImageUrl = $attendance->face_image_in_url; 

            return [
                'staff_name' => $attendance->staff->staff_name ?? 'N/A',
                'time_in' => $attendance->time_in ? Carbon::parse($attendance->time_in)->format('h:i A') : 'N/A',
                'time_out' => $attendance->time_out ? Carbon::parse($attendance->time_out)->format('h:i A') : 'N/A',
                'date' => $attendance->date ? Carbon::parse($attendance->date)->format('Y-m-d') : $today,
                'face_image_url' => $faceImageUrl, 
            
            
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
