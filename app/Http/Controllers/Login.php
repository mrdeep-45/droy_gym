<?php

namespace App\Http\Controllers;

use App\Models\Company_menu;
use App\Models\Company_submenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class Login extends Controller
{
    public function index()
    {
        $page_title = 'Login';
        $page_name = 'Login';
        return view('login', compact('page_title', 'page_name'));
    }
    public function login_process(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Enter a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters'
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $superadmin = DB::table('super_admin')->where('email', $email)->where('status', 0)->first();
        if ($superadmin && md5($password) === $superadmin->password) {
            $this->createSuperAdminSession($superadmin);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect_url' => route('dashboard')
            ]);
        }

        $company = DB::table('companies')->where('email', $email)->first();
        if ($company && md5($password) === $company->password) {
            $this->createCompanySession($company);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect_url' => route('company/dashboard')
            ]);
        }
        $staff = DB::table('mst_staff')->where('staff_email', $email)->first();
        if ($staff && md5($password) === $staff->password) {
            $this->createStaffSession($staff);
            /*
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect_url' => route('company/dashboard')
            ]);
            */
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect_url' => route('staff/dashboard')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Login failed. Please check Email & Password'
        ]);
    }


    private function createSuperAdminSession($superadmin)
    {
        session([
            'admin_id' => $superadmin->admin_id,
            'name' => $superadmin->name,
            'user_name' => $superadmin->user_name,
            'email' => $superadmin->email,
            'login_type' => 'Super Admin'
        ]);
    }

    private function createCompanySession($company)
    {
        session([
            'company_id' => $company->company_id,
            'name' => $company->name,
            // 'user_name' => $company->user_name,
            'email' => $company->email,
            'login_type' => 'Company'
        ]);
    }

    private function createStaffSession($staff)
    {
        session([
            'staff_id' => $staff->staff_id,
            'staff_name' => $staff->staff_name,
            'staff_email' => $staff->staff_email,
            'role_id' => $staff->role_id,
            'login_type' => 'Staff'
        ]);
    }

    public function logout()
    {
        $rememberToken = Session::get('remember_token');
        $userType = Session::get('remember_user_type');

        Session::flush();
        Session::regenerate();
        return redirect('/')->with('status', 'You have been logged out successfully.');
    }

    public function quick_search(Request $request)
    {
        $searchTerm = $request->input('query');
        $results = ['menus' => [], 'submenus' => []];
        $userType = session()->get('login_type');

        if (!$searchTerm) {
            return response()->json($results);
        }

        try {
            if ($userType == 'Company') {
                $companyId = session()->get('company_id');

                $menuIds = DB::table('company_permission')
                    ->where('company_id', $companyId)
                    ->pluck('c_menu_id')
                    ->unique()
                    ->toArray();

                $submenuIds = DB::table('company_permission')
                    ->where('company_id', $companyId)
                    ->whereNotNull('c_sub_menu_id')
                    ->pluck('c_sub_menu_id')
                    ->unique()
                    ->toArray();
            } elseif ($userType == 'Staff') {
                $roleId = session()->get('role_id');

                $menuIds = DB::table('role_menu_permissions')
                    ->where('role_id', $roleId)
                    ->pluck('menu_id')
                    ->unique()
                    ->toArray();

                $submenuIds = DB::table('role_menu_permissions')
                    ->where('role_id', $roleId)
                    ->whereNotNull('sub_menu_id')
                    ->pluck('sub_menu_id')
                    ->unique()
                    ->toArray();
            } else {
                return response()->json($results);
            }

            $menus = DB::table('company_menus')
                ->whereIn('c_menu_id', $menuIds)
                ->where('menu_title', 'like', '%' . $searchTerm . '%')
                ->orderBy('menu_order')
                ->where('is_submenu', 0)
                ->limit(5)
                ->get();

            $submenus = DB::table('company_submenus as s')
                ->join('company_menus as m', 's.c_menu_id', '=', 'm.c_menu_id')
                ->whereIn('s.c_sub_menu_id', $submenuIds)
                ->where('s.sub_menu_title', 'like', '%' . $searchTerm . '%')
                ->orderBy('s.submenu_order')
                ->limit(10)
                ->select('s.*', 'm.menu_title as parent_title')
                ->get();

            foreach ($menus as $menu) {

                $results['menus'][] = [
                    'title' => $menu->menu_title,
                    'icon' => $menu->menu_icon ?? 'bx bx-cube',
                    'url' => $menu->menu_route ? route($menu->menu_route) : '#',
                    'type' => 'menu'
                ];
            }

            foreach ($submenus as $submenu) {
                $results['submenus'][] = [
                    'title' => $submenu->sub_menu_title,
                    'icon' => $submenu->sub_menu_icon ?? 'bx bx-cube',
                    'url' => $submenu->sub_menu_route ? route($submenu->sub_menu_route) : '#',
                    'parent' => $submenu->parent_title ?? 'Unknown',
                    'type' => 'submenu'
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            \Log::error('Search Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function change_password(){
        $page_title = 'Change Password';
        $page_name = 'Change Password';
        return view('change_password', compact('page_title', 'page_name'));
    }

    public function update_password(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'old_password'     => 'required',
            'new_password'     => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
            'login_type'       => 'required|in:Super Admin,Company,Staff'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $loginType  = $request->login_type;
        $company_id = session()->get('company_id');
        $admin_id   = session()->get('admin_id');
        $staff_id   = session()->get('staff_id');

        $user   = null;
        $table  = null;
        $idCol  = null;
        $idVal  = null;

        switch ($loginType) {
            case 'Super Admin':
                $user  = DB::table('super_admin')->where('admin_id', $admin_id)->first();
                $table = 'super_admin';
                $idCol = 'admin_id';
                $idVal = $admin_id;
                break;

            case 'Company':
                $user  = DB::table('companies')->where('company_id', $company_id)->first();
                $table = 'companies';
                $idCol = 'company_id';
                $idVal = $company_id;
                break;

            case 'Staff':
                $user  = DB::table('mst_staff')->where('staff_id', $staff_id)->first();
                $table = 'mst_staff';
                $idCol = 'staff_id';
                $idVal = $staff_id;
                break;

            default:
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid login type'
                ], 400);
        }

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check old password
        if (md5($request->old_password) !== $user->password) {
            return response()->json([
                'status'  => false,
                'message' => 'Old password does not match'
            ], 401);
        }

            DB::table($table)
            ->where($idCol, $idVal)
            ->update([
                'password'   => md5($request->new_password),
                'updated_at' => now()
            ]);
        Session::flush();
        Session::regenerate();
        return response()->json([
            'status'  => true,
            'message' => 'Password updated successfully'
        ]);
        

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong: ' . $e->getMessage()
        ], 500);
    }
}

}
