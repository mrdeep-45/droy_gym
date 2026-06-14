<?php

use App\Models\CompanyModel;
use App\Models\DealHistory;
use App\Models\LeadHistorymodel;
use App\Models\ProductModel;
use App\Models\RoleModel;
use App\Models\CountryModel;
use App\Models\StateModel;
use App\Models\CityModel;
use App\Models\InventoryModel;
use App\Models\MaterialissueModel;
use App\Models\LeadmanageModel;
use App\Models\Company_menu;
use App\Models\Company_submenu;
use App\Models\RoleMenuPermissionModel;
use App\Models\ProductionHistoryModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Pusher\Pusher;
use function Laravel\Prompts\select;


if (!function_exists('get_session_name')) {
    function get_session_name()
    {
        $name = null;
        $role = Session::get('login_type');

        if ($role == 'Staff') {
            $staffId = Session::get('staff_id');
            if ($staffId) {
                $staff = DB::table('mst_staff')->where('staff_id', $staffId)->value('staff_name');
                if ($staff) {
                    $name = $staff;
                }
            }
        } else if ($role == 'Company') {
            $companyId = Session::get('company_id');
            if ($companyId) {
                $company = DB::table('companies')->where('company_id', $companyId)->value('name');
                if ($company) {
                    $name = $company;
                }
            }
        } else if ($role == 'Super Admin') {
            $adminId = Session::get('admin_id');
            if ($adminId) {
                $admin = DB::table('super_admin')->where('admin_id', $adminId)->value('name');
                if ($admin) {
                    $name = $admin;
                }
            }
        }

        return $name;
    }
}

if (!function_exists('get_createdby_name')) {
    function get_createdby_name($created_by)
    {
        $name = null;

        $staff = DB::table('mst_staff')->where('staff_id', $created_by)->value('staff_name');
        if ($staff) {
            $name = $staff;
        } else {
            $company = DB::table('companies')->where('company_id', $created_by)->value('name');
            if ($company) {
                $name = $company;
            } else {
                $admin = DB::table('super_admin')->where('admin_id', $created_by)->value('name');
                if ($admin) {
                    $name = $admin;
                }
            }
        }

        return $name;
    }
}

if (!function_exists('get_role_name')) {
    function get_role_name($role_id)
    {
        $role = RoleModel::where('role_id', $role_id)->where('status', 0)->first();
        return $role->role_name;
    }
}


if (!function_exists('get_source_name')) {
    function get_source_name($source_id)
    {
        $source = DB::table('mst_source')->where('source_id', $source_id)->where('status', 0)->first();
        return $source ? $source->source_name : '-';
    }
}
if (!function_exists('get_status_name')) {
    function get_status_name($status_id)
    {
        $status = DB::table('lead_status')->where('status_id', $status_id)->where('status', 0)->first();
        return $status ? $status->status_name : '-';
    }
}
if (!function_exists('get_service_name')) {
    function get_service_name($service_id)
    {
        $service = DB::table('mst_service_manage')->where('service_id', $service_id)->where('status', 0)->first();
        return $service ? $service->service_name : '-';
    }
}
if (!function_exists('get_country_name')) {
    function get_country_name($c_id)
    {
        $country = DB::table('mst_country')->where('c_id', $c_id)->where('status', 0)->first();
        return $country ? $country->country_name : '-';
    }
}

if (!function_exists('get_state_name')) {
    function get_state_name($state_id)
    {
        $state = DB::table('mst_state')->where('state_id', $state_id)->where('status', 0)->first();
        return $state ? $state->state_name : '-';
    }
}

if (!function_exists('get_city_name')) {
    function get_city_name($city_id)
    {
        $city = DB::table('mst_city')->where('city_id', $city_id)->where('status', 0)->first();
        return $city ? $city->city_name : '-';
    }
}
if (!function_exists('get_document_name')) {
    function get_document_name($dc_id)
    {
        $category = DB::table('document_category')->where('dc_id', $dc_id)->where('status', 0)->first();
        return $category ? $category->d_category : '-';
    }
}


if (!function_exists('insertLeadHistory')) {
    function insertLeadHistory($lead_id, $change_type, $reason, $table_name, $changed_by)
    {
        DB::table('lead_history')->insert([
            'lead_id' => $lead_id,
            'change_type' => $change_type,
            'reason' => $reason,
            'changed_by' => $changed_by,
            'table_name' => $table_name,
            'changed_at' => now(),
        ]);
    }
}

if (!function_exists('insert_deal_history')) {
    function insert_deal_history($dealId, $changeType, $reason = null)
    {
        DealHistory::create([
            'deal_id' => $dealId,
            'change_type' => $changeType,
            'reason' => $reason,
            'changed_by' => getCreatedBy(),
            'changed_at' => Carbon::now(),
        ]);
    }
}


if (!function_exists('getCreatedBy')) {
    function getCreatedBy()
    {
        $created_by = null;
        $role = Session::get('login_type');
        if ($role == 'Staff') {
            $created_by = Session::get('staff_id');
        } else if ($role == 'Company') {
            $created_by = Session::get('company_id');
        } else if ($role == 'Super Admin') {
            $created_by = '-' . Session::get('admin_id');
        }
        return $created_by;
    }
}

if (!function_exists('getUpdatedBy')) {
    function getUpdatedBy()
    {
        $updated_by = null;
        $role = Session::get('login_type');
        if ($role == 'Staff') {
            $updated_by = Session::get('staff_id');
        } else if ($role == 'Company') {
            $updated_by = Session::get('company_id');
        } else if ($role == 'Super Admin') {
            $updated_by = '-' . Session::get('admin_id');
        }
        return $updated_by;
    }
}
if (!function_exists('get_company_details')) {
    function get_company_details($company_id)
    {
        
        return CompanyModel::where('company_id', $company_id)->where('status', 0)->first();
    }
}

if (!function_exists('get_company_details_one')) {
    function get_company_details_one()
    {
        return CompanyModel::where('status', 0)->first();
    }
}

if (!function_exists('updateInventory')) {
    //  function updateInventory($rm_id, $qty)
    // {
    //     $existing = InventoryModel::where('rm_id', $rm_id)->first();

    //     if ($existing) {
    //         $existing->update([
    //             'avl_qty'   => $existing->avl_qty + $qty,
    //             'updated_by'=> getCreatedBy(),
    //             'updated_at'=> Carbon::now(),
    //         ]);
    //     } else {
    //         InventoryModel::create([
    //             'rm_id'     => $rm_id,
    //             'avl_qty'   => $qty,
    //             'created_by'=> getCreatedBy(),
    //             'created_at'=> Carbon::now(),
    //         ]);
    //     }
    // }

    function updateInventory($rm_id, $qty)
    {
        $qty = (float) $qty;
        if ($qty <= 0) {
            return;
        }
        $inventory = InventoryModel::where('rm_id', $rm_id)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->avl_qty = (float) $inventory->avl_qty + $qty;
            $inventory->updated_by = getCreatedBy();
            $inventory->updated_at = Carbon::now();
            $inventory->save();
        } else {
            InventoryModel::create([
                'rm_id' => $rm_id,
                'avl_qty' => $qty,
                'created_by' => getCreatedBy(),
                'created_at' => Carbon::now(),
            ]);
        }
    }
}

if (!function_exists('updateIssueInventory')) {
    function updateIssueInventory($rm_id, $qty)
    {
        $existing = InventoryModel::where('rm_id', $rm_id)->first();
        if ($existing) {
            $newQty = max(0, $existing->avl_qty - $qty);
            InventoryModel::where('rm_id', $rm_id)->update([
                'avl_qty' => $newQty,
                'updated_by' => getCreatedBy(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}

if (!function_exists('numberToWords')) {
    function numberToWords($number)
    {
        $f = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }
}

if (!function_exists('get_lead_name')) {
    function get_lead_name($lead_id)
    {
        $lead = LeadmanageModel::where('lead_id', $lead_id)->first();

      

        if (!$lead) {
            return [
                'lead_name' => '-',
                'lead_no' => '-',
                'company_name' => '-',
                'contact_person_name' => '-',
                'email' => '-',
                'phone' => '-',
                'source' => '-',
            ];
        }

        return [
            'lead_name' => $lead->lead_name,
            'lead_no' => $lead->lead_no,
            'company_name' => $lead->company_name,
            'contact_person_name' => $lead->contact_person_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'source' => $lead->source,
        ];
    }
}

if (!function_exists('get_product_details')) {
    function get_product_details($product_id)
    {
        $product = ProductModel::where('product_id', $product_id)->first();

        if (!$product) {
            return [
                'product_id' => '-',
                'category_id' => '-',
                'prod_code' => '-',
                'prod_name' => '-',
                'prod_desc' => '-',
                'unit_id' => '-',
                'hsn_code' => '-',
                'gst' => '-',
                'temp_range' => '-',
                'validation_hrs' => '-',
                'dimensions' => '-',
                'external_dimensions' => '-',
                'internal_dimensions' => '-',
                'payload_dimensions' => '-',
                'usable_capacity' => '-',
                'gross_weight' => '-',
                'pcm_volume' => '-',
            ];
        }

        return [
            'product_id' => $product->product_id,
            'category_id' => $product->category_id,
            'prod_code' => $product->prod_code,
            'prod_name' => $product->prod_name,
            'prod_desc' => $product->prod_desc,
            'unit_id' => $product->unit_id,
            'hsn_code' => $product->hsn_code,
            'gst' => $product->gst,
            'temp_range' => $product->temp_range,
            'validation_hrs' => $product->validation_hrs,
            'dimensions' => $product->dimensions,
            'external_dimensions' => $product->external_dimensions,
            'internal_dimensions' => $product->internal_dimensions,
            'payload_dimensions' => $product->payload_dimensions,
            'usable_capacity' => $product->usable_capacity,
            'gross_weight' => $product->gross_weight,
            'pcm_volume' => $product->pcm_volume,
        ];
    }
}
if (!function_exists('get_quotation_terms')) {
    function get_quotation_terms($module)
    {
        $term = DB::table('terms_master')->where('module', $module)->first();
        return $term ? $term->terms : '';
    }
}
if (!function_exists('getpermission')) {
    function getpermission($request)
    {
        $role_id = Session::get('role_id');
        $route   = $request->route()->getName();
        $menu = Company_menu::where("menu_route", $route)->first();
        $sub_menu = Company_submenu::where("sub_menu_route", $route)->first();
        $permission = null;
        if ($menu) {
            $permission = RoleMenuPermissionModel::where('role_id', $role_id)
                ->where('menu_id', $menu->c_menu_id)
                ->first();
        } elseif ($sub_menu) {
            $permission = RoleMenuPermissionModel::where('role_id', $role_id)
                ->where('sub_menu_id', $sub_menu->c_sub_menu_id)
                ->first();
        }

        if ($permission) {
            return [
                'can_view'   => $permission->can_view,   
                'can_create'    => $permission->can_create,    
                'can_update'   => $permission->can_update,   
                'can_delete' => $permission->can_delete,
            ];
        }
        return [
            'can_view'   => 0,
            'can_create'    => 0,
            'can_update'   => 0,
            'can_delete' => 0,
        ];
    }
}

if (!function_exists('get_email_config')) {
    function get_email_config()
    {
        $row = DB::table('mail_config')->where('id', 1)->first();
    //   \Log::info('Mail config row:', (array) $row);


        if (!$row) {
            return null;
        }

        Config::set('mail.default', $row->MAIL_MAILER ?? 'smtp');

        Config::set('mail.mailers.smtp.transport', $row->MAIL_MAILER ?? 'smtp');
        Config::set('mail.mailers.smtp.host', $row->MAIL_HOST ?? '127.0.0.1');
        Config::set('mail.mailers.smtp.port', $row->MAIL_PORT ?? 2525);
        Config::set('mail.mailers.smtp.username', $row->MAIL_USERNAME ?? null);
        Config::set('mail.mailers.smtp.password', $row->MAIL_PASSWORD ?? null);
        Config::set('mail.mailers.smtp.encryption', $row->MAIL_ENCRYPTION ?? null);

        Config::set('mail.from.address', $row->MAIL_FROM_ADDRESS ?? 'hello@example.com');
        Config::set('mail.from.name', $row->MAIL_FROM_NAME ?? config('app.name'));

    }
}
if (!function_exists('set_email_config')) {
    function set_email_config(array $data)
    {

        $allowed = [
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));

        if (empty($filtered)) {
            return false;
        }

        $exists = DB::table('mail_config')->where('id', 1)->exists();

        if ($exists) {
            DB::table('mail_config')->where('id', 1)->update($filtered);
        } else {
            $filtered['id'] = 1;
            DB::table('mail_config')->insert($filtered);
        }

        return true;
    }
}

if (!function_exists('createproductionhistory')) {

    function createproductionhistory($wo_id, $product_id,$description)
    {
            ProductionHistoryModel::create([
                'wo_id' => $wo_id,
                'product_id' => $product_id,
                'description' => $description,
                'created_by' => getCreatedBy(),
                'created_at' => Carbon::now(),
            ]);
        
    }
}



