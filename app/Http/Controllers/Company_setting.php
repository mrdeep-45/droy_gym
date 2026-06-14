<?php

namespace App\Http\Controllers;

use App\Models\CompanyModel;
use App\Models\Company_submenu;
use App\Models\Company_menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class Company_setting extends Controller
{
    protected $actual_path;
    public function __construct()
    {
        $this->actual_path = config('app.actual_url') . '/uploads/';
    }

    public function index()
    {
        $page_title = 'Profile Setting';
        $page_name = 'Profile Setting';
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
        return view('company/setting/profile', compact('page_title', 'page_name', 'menus'));
    }
    public function edit($id)
    {
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
}
