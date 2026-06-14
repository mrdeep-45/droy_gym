<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Models\Menu_settingModal;
use App\Models\SubmenuModal;
use App\Models\MenutypeModel;
use App\Models\RoleModel;
use App\Models\RoleMenuPermission;
use App\Models\DashboardModel;
use App\Models\LeadmanageModel;
use App\Models\StaffModel;

class Permission extends Controller
{
    public function index()
    {
        $page_title = 'Menu Setting';
        $page_name = 'Menu Setting';

        $menus = Menu_settingModal::where('status', 0)->get();
        $submenus = SubmenuModal::where('status', 0)->get();
        $roles = RoleModel::where('status', 0)->get();
        $dashboardItems = DashboardModel::where('status', 0)->get();
        $AdditionaldashboardItems = DashboardModel::where('status', 2)->get();




        $menuList = [];

        foreach ($menus as $menu) {
            $menuList[$menu->menu_id] = [
                'menu_id' => $menu->menu_id,
                'menu_title' => $menu->menu_title,
                'submenus' => [],
            ];
        }

        foreach ($submenus as $submenu) {
            if (isset($menuList[$submenu->menu_id])) {
                $menuList[$submenu->menu_id]['submenus'][] = [
                    'sub_menu_id' => $submenu->sub_menu_id,
                    'sub_menu_title' => $submenu->sub_menu_title,
                ];
            }
        }

        $staff = StaffModel::get();
        $total_lead_count = LeadmanageModel::count();
        $pending_lead_count = LeadmanageModel::where('status', 0)->count();
        $prospect_lead_count = LeadmanageModel::where('status', 1)->count();
        $proposal_lead_count = LeadmanageModel::where('status', 2)->count();

        return view('admin/settings/permission', compact('page_title', 'page_name', 'menuList', 'roles', 'dashboardItems', 'staff', 'total_lead_count', 'pending_lead_count', 'prospect_lead_count', 'proposal_lead_count', 'AdditionaldashboardItems'));
    }
    public function get_role_permissions(Request $request)
    {
        $role_id = $request->role_id;

        $permissions = DB::table('role_menu_permissions')
            ->where('role_id', $role_id)
            ->get();

        return response()->json($permissions);
    }

    public function store_update(Request $request)
    {
        $role_id = $request->role_id;

        if (!$role_id) {
            return response()->json(['message' => 'Role ID is required'], 422);
        }

        DB::table('role_menu_permissions')->where('role_id', $role_id)->delete();

        foreach ($request->input('menu_id', []) as $menu_id) {

            $hasMenuPermission = isset($request["menu_view"][$menu_id]) && $request["menu_view"][$menu_id] == 1 ||
                isset($request["menu_create"][$menu_id]) && $request["menu_create"][$menu_id] == 1 ||
                isset($request["menu_update"][$menu_id]) && $request["menu_update"][$menu_id] == 1 ||
                isset($request["menu_delete"][$menu_id]) && $request["menu_delete"][$menu_id] == 1;

            if ($hasMenuPermission) {
                DB::table('role_menu_permissions')->insert([
                    'role_id' => $role_id,
                    'menu_id' => $menu_id,
                    'sub_menu_id' => null,
                    'can_view' => $request["menu_view"][$menu_id] ?? 0,
                    'can_create' => $request["menu_create"][$menu_id] ?? 0,
                    'can_update' => $request["menu_update"][$menu_id] ?? 0,
                    'can_delete' => $request["menu_delete"][$menu_id] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (['view', 'create', 'update', 'delete'] as $action) {
                if (isset($request["submenu_{$action}"][$menu_id])) {
                    foreach ($request["submenu_{$action}"][$menu_id] as $sub_menu_id => $value) {

                        $hasSubmenuPermission =
                            ($request["submenu_view"][$menu_id][$sub_menu_id] ?? 0) == 1 ||
                            ($request["submenu_create"][$menu_id][$sub_menu_id] ?? 0) == 1 ||
                            ($request["submenu_update"][$menu_id][$sub_menu_id] ?? 0) == 1 ||
                            ($request["submenu_delete"][$menu_id][$sub_menu_id] ?? 0) == 1;

                        if ($hasSubmenuPermission) {
                            $exists = DB::table('role_menu_permissions')->where([
                                'role_id' => $role_id,
                                'menu_id' => $menu_id,
                                'sub_menu_id' => $sub_menu_id
                            ])->first();

                            if (!$exists) {
                                DB::table('role_menu_permissions')->insert([
                                    'role_id' => $role_id,
                                    'menu_id' => $menu_id,
                                    'sub_menu_id' => $sub_menu_id,
                                    'can_view' => $request["submenu_view"][$menu_id][$sub_menu_id] ?? 0,
                                    'can_create' => $request["submenu_create"][$menu_id][$sub_menu_id] ?? 0,
                                    'can_update' => $request["submenu_update"][$menu_id][$sub_menu_id] ?? 0,
                                    'can_delete' => $request["submenu_delete"][$menu_id][$sub_menu_id] ?? 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            } else {
                                DB::table('role_menu_permissions')
                                    ->where([
                                        'role_id' => $role_id,
                                        'menu_id' => $menu_id,
                                        'sub_menu_id' => $sub_menu_id
                                    ])
                                    ->update([
                                        'can_view' => $request["submenu_view"][$menu_id][$sub_menu_id] ?? 0,
                                        'can_create' => $request["submenu_create"][$menu_id][$sub_menu_id] ?? 0,
                                        'can_update' => $request["submenu_update"][$menu_id][$sub_menu_id] ?? 0,
                                        'can_delete' => $request["submenu_delete"][$menu_id][$sub_menu_id] ?? 0,
                                        'updated_at' => now(),
                                    ]);
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully!'
        ]);
    }

    public function list(Request $request)
    {
        $columns = [
            0 => 'sr_no',
            1 => 'role_name',
            2 => 'menu_title',
            3 => 'sub_menu_data',
            4 => 'action'
        ];

        $query = Menu_settingModal::where('status', 0)->orderBy('menu_order', 'asc');

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where('menu_title', 'LIKE', "%$searchValue%");
            $totalFiltered = $query->count();
        }

        $orderColumn = $columns[$orderColumnIndex] ?? 'menu_id';

        if (in_array($orderColumn, ['menu_id', 'menu_title'])) {
            $query->orderBy($orderColumn, $orderDirection);
        }

        $query->offset($start)
            ->limit($limit);

        $data = $query->get();
        $formattedData = [];
        $sr_no = $start + 1;


        $allRoleIds = DB::table('role_menu_permissions')
            ->whereIn('menu_id', $data->pluck('menu_id'))
            ->pluck('role_id')
            ->unique()
            ->toArray();

        $allRoles = DB::table('tbl_role')
            ->whereIn('role_id', $allRoleIds)
            ->get()
            ->keyBy('role_id');

        // Process each role separately
        foreach ($allRoles as $role) {
            $role_id = $role->role_id;
            $role_name = $role->role_name;

            $menuBlocks = [];
            $actionButtons = '';


            foreach ($data as $row) {
                $menu_id = $row->menu_id;

                // Check if this role has any permissions for this menu
                $hasPermission = DB::table('role_menu_permissions')
                    ->where('menu_id', $menu_id)
                    ->where('role_id', $role_id)
                    ->exists();

                if (!$hasPermission) {
                    continue;
                }


                $menuPermission = DB::table('role_menu_permissions')
                    ->where('menu_id', $menu_id)
                    ->where('role_id', $role_id)
                    ->whereNull('sub_menu_id')
                    ->first();

                $mainPerms = [];
                if ($menuPermission) {
                    if ($menuPermission->can_view) $mainPerms[] = 'View';
                    if ($menuPermission->can_create) $mainPerms[] = 'Create';
                    if ($menuPermission->can_update) $mainPerms[] = 'Update';
                    if ($menuPermission->can_delete) $mainPerms[] = 'Delete';
                }


                $submenus = DB::table('submenus')
                    ->where('menu_id', $menu_id)
                    ->orderBy('submenu_order', 'asc')
                    ->get();

                $subMenuPerms = [];
                foreach ($submenus as $submenu) {
                    $subPermission = DB::table('role_menu_permissions')
                        ->where('menu_id', $menu_id)
                        ->where('sub_menu_id', $submenu->sub_menu_id)
                        ->where('role_id', $role_id)
                        ->first();

                    $subPerms = [];
                    if ($subPermission) {
                        if ($subPermission->can_view) $subPerms[] = 'View';
                        if ($subPermission->can_create) $subPerms[] = 'Create';
                        if ($subPermission->can_update) $subPerms[] = 'Update';
                        if ($subPermission->can_delete) $subPerms[] = 'Delete';
                    }

                    if (!empty($subPerms)) {
                        $subMenuPerms[] = [
                            'title' => $submenu->sub_menu_title,
                            'perms' => implode(', ', $subPerms)
                        ];
                    }
                }


                $menuBlock = [
                    'title' => '<div class="fw-bold">' . $row->menu_title . '</div>',
                    'perms' => ''
                ];


                if (!empty($mainPerms)) {
                    $menuBlock['perms'] .= '<div>Permission :- (' . implode(', ', $mainPerms) . ')</div>';
                }

                if (!empty($subMenuPerms)) {
                    foreach ($subMenuPerms as $sub) {
                        $menuBlock['perms'] .= '<div>' . $sub['title'] . ': (' . $sub['perms'] . ')</div>';
                    }
                }


                $menuBlocks[] = $menuBlock;

                // Action buttons (only add once per role)
                if (empty($actionButtons)) {
                    $actionButtons = '<div class="d-flex justify-content-center gap-2">';
                    $actionButtons .= '<a href="#" class="btn btn-icon btn-primary-light rounded-pill btn-wave waves-effect waves-light tooltip-init getEdit"
                        data-role-id="' . $role_id . '" title="Edit Permissions">
                        <i class="bx bx-edit"></i>
                        </a>';
                    // In your list() method where you create the action buttons:
                    $actionButtons .= '<a href="#" class="btn btn-icon btn-danger-light rounded-pill btn-wave waves-effect waves-light tooltip-init"
data-role-id="' . $role_id . '"
data-role-name="' . htmlspecialchars($role_name) . '"
title="Delete Permissions"
data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">
<i class="bx bx-trash"></i>
</a>';
                    $actionButtons .= '</div>';
                }
            }


            if (!empty($menuBlocks)) {
                $menuTitles = '';
                $menuPermissions = '';

                foreach ($menuBlocks as $block) {
                    $menuTitles .= $block['title'];
                    $menuPermissions .= $block['perms'];
                }

                $formattedData[] = [
                    'sr_no' => $sr_no++,
                    'role_name' => $role_name,
                    'menu_title' => $menuTitles,
                    'sub_menu_data' => $menuPermissions,
                    'action' => $actionButtons
                ];
            }
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $formattedData
        ]);
    }


    public function get_role_data($id)
    {

        $role = DB::table('role_menu_permissions')->where('role_id', $id)->get();
        // dd($submenus);
        return response()->json([
            'role_id' => $id,
            'role' => $role,
        ]);
    }


    public function deleteRolePermissions(Request $request)
    {
        try {
            $roleId = $request->input('role_id');

            // Delete all permissions for this role
            DB::table('role_menu_permissions')
                ->where('role_id', $roleId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permissions deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}
