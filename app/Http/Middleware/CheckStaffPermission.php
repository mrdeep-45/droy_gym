<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class CheckStaffPermission
{
    public function handle($request, Closure $next)
    {
        if (session('login_type') === 'Staff') {
            $roleId = session('role_id');
            $currentRoute = Route::currentRouteName();

            $menu = DB::table('company_menus')
                ->where('menu_route', $currentRoute)
                ->where('status', 0)
                ->first();

            $submenu = DB::table('company_submenus')
                ->where('sub_menu_route', $currentRoute)
                ->where('status', 0)
                ->first();

            $permissionExists = false;

            if ($menu) {
                $permissionExists = DB::table('role_menu_permissions')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menu->c_menu_id)
                    ->where('can_view', 1)
                    ->exists();
            }

            if ($submenu && !$permissionExists) {
                $permissionExists = DB::table('role_menu_permissions')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $submenu->c_menu_id)
                    ->where('sub_menu_id', $submenu->c_sub_menu_id)
                    ->where('can_view', 1)
                    ->exists();
            }

            if (!$permissionExists) {
                session()->flush();
                return redirect()->route('logout');
            }
        }

        return $next($request);
    }
}
