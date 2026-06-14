<?php

namespace App\Http\Controllers;

use App\Models\Company_permission_Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Models\Company_menu;
use App\Models\Company_submenu;
use App\Models\MenutypeModel;

class Super_menu_submenu extends Controller
{
    public function index()
    {
        $page_title = 'Menu Setting';
        $page_name = 'Menu Setting';

        $menu_type = MenutypeModel::all();
        return view('superadmin/menu_submenu', compact('page_title', 'page_name', 'menu_type'));
    }

    public function menu_store(Request $request)
    {
        $menu_type_id = $request->input('menu_type_id');

        $RoleName = MenutypeModel::where('menu_type_id', $menu_type_id)->first();
        if (!$RoleName) {
            $Role_name = MenutypeModel::create([
                'type_name' => $menu_type_id,
            ]);
            $menu_type_id = $Role_name->menu_type_id;
        }

        $rules = [
            'c_menu_id' => 'nullable|integer',
            'menu_title' => 'required|string|max:255',
            'menu_route' => 'nullable|string|max:255',
            'menu_icon' => 'required|string|max:255',
            'is_submenu' => 'required|boolean',
        ];

        if ($request->is_submenu == 1) {
            $rules = array_merge($rules, [
                'sub_menu_title.*' => 'required|string|max:255',
                'sub_menu_route.*' => 'required|string|max:255',
            ]);
        }

        $validatedData = $request->validate($rules);

        if ($request->filled('c_menu_id')) {

            $menu = Company_menu::find($request->c_menu_id);
            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu not found!',
                ], 404);
            }

            $menu->update([
                'menu_title' => $validatedData['menu_title'],
                'menu_icon' => $validatedData['menu_icon'] ?? null,
                'menu_route' => $validatedData['menu_route'] ?? null,
                'is_submenu' => (int)$validatedData['is_submenu'],
                'menu_type_id' => $menu_type_id
            ]);

            if ((int)$validatedData['is_submenu'] === 1) {
                foreach ($validatedData['sub_menu_title'] as $index => $sub_menu_title) {
                    $existingSubmenu = Company_submenu::where('c_menu_id', $menu->c_menu_id)
                        ->skip($index)
                        ->first();

                    if ($existingSubmenu) {
                        $hasPermission = Company_permission_Model::where('c_sub_menu_id', $existingSubmenu->c_sub_menu_id)->exists();

                        if ($hasPermission) {
                            $existingSubmenu->update([
                                'sub_menu_title' => $sub_menu_title,
                                'sub_menu_route' => $validatedData['sub_menu_route'][$index],
                            ]);
                        } else {
                            $existingSubmenu->delete();

                            Company_submenu::create([
                                'c_menu_id' => $menu->c_menu_id,
                                'sub_menu_title' => $sub_menu_title,
                                'sub_menu_icon' => '<i class="fa-light fa-house"></i>',
                                'sub_menu_route' => $validatedData['sub_menu_route'][$index],
                                'status' => 0,
                                'created_by' => getCreatedBy()
                            ]);
                        }
                    } else {
                        Company_submenu::create([
                            'c_menu_id' => $menu->c_menu_id,
                            'sub_menu_title' => $sub_menu_title,
                            'sub_menu_icon' => '<i class="fa-light fa-house"></i>',
                            'sub_menu_route' => $validatedData['sub_menu_route'][$index],
                            'status' => 0,
                            'created_by' => getCreatedBy()
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Menu and submenus updated successfully!',
            ]);
        }

        $menu = Company_menu::create([
            'menu_title' => $validatedData['menu_title'],
            'menu_icon' => $validatedData['menu_icon'] ?? null,
            'menu_route' => $validatedData['menu_route'] ?? null,
            'status' => 0,
            'is_submenu' => (int)$validatedData['is_submenu'],
            'menu_type_id' => $menu_type_id,
            'created_by' => getCreatedBy()
        ]);

        if ((int)$validatedData['is_submenu'] === 1) {
            foreach ($validatedData['sub_menu_title'] as $index => $sub_menu_title) {
                Company_submenu::create([
                    'c_menu_id' => $menu->c_menu_id,
                    'sub_menu_title' => $sub_menu_title,
                    'sub_menu_icon' => '<i class="fa-light fa-house"></i>' ?? null,
                    'sub_menu_route' => $validatedData['sub_menu_route'][$index],
                    'status' => 0,
                    'created_by' => getCreatedBy()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu and submenus added successfully!',
        ]);
    }



    public function menu_data_fetch(Request $request)
    {
        $columns = [
            0 => 'c_menu_id',
            1 => 'menu_title',
            2 => 'menu_icon',
            3 => 'menu_routs',
            4 => 'sub_menu_title',
            5 => 'sub_menu_route',
        ];

        $query = Company_menu::where('status', 0)->orderBy('menu_order', 'asc')->distinct();

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'asc'));
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($query) use ($searchValue) {
                $query->orwhere('menus.menu_title', 'LIKE', "%$searchValue%");
            });
            $totalFiltered = $query->count();
        }

        $orderColumn = $columns[$orderColumnIndex] ?? 'menus.c_menu_id';
        $query->orderBy($orderColumn, $orderDirection)
            ->offset($start)
            ->limit($limit);

        $data = $query->get();
        $formattedData = [];
        $sr_no = $start + 1;
        foreach ($data as $row) {
            $c_menu_id = $row->c_menu_id;
            $submenu = DB::table('company_submenus')
                ->select('sub_menu_title')
                ->where('c_menu_id', $c_menu_id)
                ->orderBy('submenu_order', 'asc')
                ->get();

            $sub_menu_title = [];
            foreach ($submenu as $list) {
                $sub_menu_title[] = '• ' . $list->sub_menu_title;
            }
            $sub_menu_name_str = implode('<br>', $sub_menu_title);


            $formattedData[] = [
                'sr_no' => $sr_no++,
                'c_menu_id' => $row->c_menu_id,
                'menu_title' => $row->menu_title,
                'menu_icon' => $row->menu_icon,
                'menu_route' => $row->menu_route,
                'sub_menu_title' => $sub_menu_name_str,
                'sub_menu_route' => $row->sub_menu_route,
                'Action' => '
                    <div class="row">
                        <div class="d-flex mt-0">
                            <a href="#" id="getEdit" data-c_menu_id="' . $row->c_menu_id . '" title="Edit Menu" class="btn btn-icon btn-primary-light rounded-pill btn-wave waves-effect waves-light tooltip-init"><i class="bx bx-edit"></i></a>&nbsp;&nbsp;
                         <a href="#delete' . $row->c_menu_id . '" data-bs-target="#delete' . $row->c_menu_id . '" data-bs-toggle="modal" title="Delete Menu" class="btn btn-icon btn-danger-light rounded-pill btn-wave waves-effect waves-light tooltip-init delete-btn" data-menu-id="' . $row->c_menu_id . '" data-menu-title="' . $row->menu_title . '">
    <i class="bx bx-trash"></i>
</a>
</div>
<div class="modal modal-blur fade" id="delete' . $row->c_menu_id . '" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z" />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>
                <div class="text-muted">Do you really want to remove <b><span id="menuTitleToDelete"></span></b>?</div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col">
                        <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    <div class="col">
                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>',
            ];
        }
        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "start" => intval($start),
            "data" => $formattedData,
        ];

        return response()->json($json_data);
    }

    // public function get_menu_data($id)
    // {

    //     $menu = DB::table('company_menus')->where('c_menu_id', $id)->first();

    //     $submenus = DB::table('company_submenus')->where('c_menu_id', $id)->get();
    //     // dd($submenus);
    //     return response()->json([
    //         'menu' => $menu,
    //         'submenus' => $submenus
    //     ]);
    // }

    public function getMenuData(Request $request)
    {
        $c_menu_id = $request->c_menu_id;

        $menu = DB::table('company_menus')->where('c_menu_id', $c_menu_id)->first();
        $submenus = DB::table('company_submenus')->where('c_menu_id', $c_menu_id)->get();

        return response()->json([
            'success' => true,
            'menu' => $menu,
            'submenus' => $submenus
        ]);
    }
    public function destroy($id)
    {
        $submenu = Company_submenu::find($id);

        if (!$submenu) {
            return response()->json(['success' => false, 'message' => 'Submenu not found']);
        }

        $submenu->delete();

        return response()->json(['success' => true]);
    }


    public function delete(Request $request)
    {
        $submenuId = $request->input('submenu_id');
        $menuId = $request->input('c_menu_id');

        $query = Company_submenu::where('c_sub_menu_id', $submenuId)->delete();

        if ($query) {
            $remainingSubmenus = Company_submenu::where('c_menu_id', $menuId)
                ->where('status', 0)
                ->count();
            if ($remainingSubmenus == 0) {
                $menuUpdate = Company_menu::where('c_menu_id', $menuId)
                    ->update(['is_submenu' => 1]);

                if ($menuUpdate) {
                    return response()->json(['success' => true, 'message' => 'Submenu Deleted and Menu Updated']);
                } else {
                    return response()->json(['success' => false, 'message' => 'Failed to update menu after deleting submenu']);
                }
            }

            return response()->json(['success' => true, 'message' => 'Submenu Deleted Successfully']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error deleting submenu']);
        }
    }

    public function deleteMenu(Request $request)
    {
        $request->validate([
            'c_menu_id' => 'required|integer|exists:company_menus,c_menu_id'
        ]);

        try {
            DB::beginTransaction();

            Company_submenu::where('c_menu_id', $request->c_menu_id)->delete();

            Company_menu::where('c_menu_id', $request->c_menu_id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data deleted successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu: ' . $e->getMessage()
            ], 500);
        }
    }


    public function fetchOrdering()
    {
        $menus = DB::table('company_menus')
            ->orderBy('menu_order')
            ->get();

        foreach ($menus as $menu) {
            $menu->submenus = DB::table('company_submenus')
                ->where('c_menu_id', $menu->c_menu_id)
                ->orderBy('submenu_order') // optional
                ->get();
        }

        return response()->json($menus);
    }


    public function updateOrdering(Request $request)
    {
        $request->validate([
            'order' => 'required|json'
        ]);

        $order = json_decode($request->order, true);

        if (!is_array($order)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid order data'], 400);
        }

        try {
            DB::beginTransaction();

            foreach ($order as $item) {
                DB::table('company_menus')
                    ->where('c_menu_id', $item['id'])
                    ->update(['menu_order' => $item['order']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateSubmenuOrdering(Request $request)
    {
        $request->validate([
            'order' => 'required|json',
            'c_menu_id' => 'required|integer'
        ]);
        // dd($request->all());

        $order = json_decode($request->order, true);

        if (!is_array($order)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid order data'], 400);
        }

        try {
            DB::beginTransaction();

            foreach ($order as $item) {
                DB::table('company_submenus')
                    ->where('c_sub_menu_id', $item['id'])
                    ->where('c_menu_id', $request->c_menu_id)
                    ->update(['submenu_order' => $item['order']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
