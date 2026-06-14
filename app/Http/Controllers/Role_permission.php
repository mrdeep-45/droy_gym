<?php

namespace App\Http\Controllers;

use App\Models\RoleMenuPermissionModel;
use App\Models\RoleModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\MenutypeModel;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Role_permission extends Controller
{
    public function index()
    {
        $menu_type = MenutypeModel::all();
        $page_title = 'Role Permission';
        $page_name = 'Role Permission';

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
        $role = RoleModel::where('status', '0')->get();
        return view('company/setting/role_permission', compact('page_title', 'page_name', 'menu_type', 'menus', 'role'));
    }
    public function create_role(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:255|unique:tbl_role,role_name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $roleId = DB::table('tbl_role')->insertGetId([
                'role_name' => $request->role_name,
                'created_at' => now(),
                'created_by' => getCreatedBy(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully!',
                'data' => [
                    'role_id' => $roleId,
                    'role_name' => $request->role_name
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }
    public function save_permissions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:tbl_role,role_id',
            'permissions_menu' => 'sometimes|array',
            'permissions' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $roleId = $request->role_id;

            RoleMenuPermissionModel::where('role_id', $roleId)->delete();

            // Insert permissions_menu if provided
            if ($request->has('permissions_menu')) {
                foreach ($request->permissions_menu as $menuId => $permissions) {
                    RoleMenuPermissionModel::create([
                        'role_id' => $roleId,
                        'menu_id' => $menuId,
                        'sub_menu_id' => null,
                        'can_view' => !empty($permissions['view']) ? 1 : 0,
                        'can_create' => !empty($permissions['create']) ? 1 : 0,
                        'can_update' => !empty($permissions['update']) ? 1 : 0,
                        'can_delete' => !empty($permissions['delete']) ? 1 : 0,
                        'status' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Insert permissions for submenus if provided
            if ($request->has('permissions')) {
                foreach ($request->permissions as $submenuId => $permissions) {
                    $submenu = DB::table('company_submenus')
                        ->select('c_menu_id')
                        ->where('c_sub_menu_id', $submenuId)
                        ->first();

                    if ($submenu) {
                        RoleMenuPermissionModel::create([
                            'role_id' => $roleId,
                            'menu_id' => $submenu->c_menu_id,
                            'sub_menu_id' => $submenuId,
                            'can_view' => !empty($permissions['view']) ? 1 : 0,
                            'can_create' => !empty($permissions['create']) ? 1 : 0,
                            'can_update' => !empty($permissions['update']) ? 1 : 0,
                            'can_delete' => !empty($permissions['delete']) ? 1 : 0,
                            'status' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permissions saved successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save permissions: ' . $e->getMessage()
            ], 500);
        }
    }
    public function list(Request $request)
    {
        $permissions = RoleMenuPermissionModel::query()
            ->with(['menu', 'submenu', 'role'])
            ->get()
            ->groupBy(['role_id', 'menu_id']);

        $processedData = [];
        $index = 1;

        foreach ($permissions as $roleId => $menuGroups) {
            foreach ($menuGroups as $menuId => $items) {
                $firstItem = $items->first();

                $processedRow = [
                    'DT_RowIndex' => $index++,
                    'role_id' => $roleId,
                    'role_name' => get_role_name($roleId),
                    'menu_id' => $menuId,
                    'menu_name' => $firstItem->menu ? $firstItem->menu->menu_title : 'N/A',
                    'submenu_data' => $items->map(function ($permission) {
                        return [
                            'submenu_id' => $permission->sub_menu_id,
                            'submenu_name' => $permission->submenu ? $permission->submenu->sub_menu_title : 'N/A',
                            'can_view' => $permission->can_view,
                            'can_create' => $permission->can_create,
                            'can_update' => $permission->can_update,
                            'can_delete' => $permission->can_delete,
                            'id' => $permission->id
                        ];
                    })->toArray()
                ];

                $processedData[] = $processedRow;
            }
        }

        return DataTables::of($processedData)
            ->addIndexColumn()
            ->addColumn('role_name', function ($row) {
                return $row['role_name'];
            })
            ->addColumn('menu_name', function ($row) {
                return $row['menu_name'];
            })
            ->addColumn('submenu_name', function ($row) {
                return collect($row['submenu_data'])->map(function ($submenu) {
                    return $submenu['submenu_name'] !== 'N/A' ? $submenu['submenu_name'] : '';
                })->filter()->implode('<br>');
            })
            ->addColumn('permissions', function ($row) {
                return collect($row['submenu_data'])->map(function ($submenu) {
                    $badges = [];
                    if ($submenu['can_view']) $badges[] = '<span class="badge rounded-pill bg-primary-transparent">View</span>';
                    if ($submenu['can_create']) $badges[] = '<span class="badge rounded-pill bg-success-transparent">Create</span>';
                    if ($submenu['can_update']) $badges[] = '<span class="badge rounded-pill bg-warning-transparent">Update</span>';
                    if ($submenu['can_delete']) $badges[] = '<span class="badge rounded-pill bg-danger-transparent">Delete</span>';

                    return implode(' ', $badges) ?: '<span class="text-muted">No permissions</span>';
                })->implode('<br>');
            })
            ->addColumn('action', function ($row) {
                $actions = collect($row['submenu_data'])->map(function ($submenu) {
                    return '
          ';
                })->implode('<br>');

                return $actions;
            })
            ->rawColumns(['submenu_name', 'permissions', 'action'])
            ->toJson();
    }
    public function get_permissions($roleId)
    {
        $permissions = RoleMenuPermissionModel::where('role_id', $roleId)->get();

        $formattedPermissions = [
            'permissions_menu' => [],
            'permissions' => []
        ];

        foreach ($permissions as $permission) {
            if ($permission->sub_menu_id === null) {
                $formattedPermissions['permissions_menu'][$permission->menu_id] = [
                    'view' => $permission->can_view,
                    'create' => $permission->can_create,
                    'update' => $permission->can_update,
                    'delete' => $permission->can_delete
                ];
            } else {
                $formattedPermissions['permissions'][$permission->sub_menu_id] = [
                    'view' => $permission->can_view,
                    'create' => $permission->can_create,
                    'update' => $permission->can_update,
                    'delete' => $permission->can_delete
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $formattedPermissions
        ]);
    }
    public function delete(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:tbl_role,role_id'
        ]);

        // Delete all permissions for this role
        $deleted = RoleMenuPermissionModel::where('role_id', $request->role_id)->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No permissions found for this role'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'All permissions for this role deleted successfully'
        ]);
    }
}
