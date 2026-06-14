<?php

use App\Models\RoleModel;
use App\Models\CountryModel;
use App\Models\StateModel;
use App\Models\CityModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Routing\Router;
use function Laravel\Prompts\select;




if (!function_exists('checkPermissions')) {
    function checkPermissions($routeName)
    {
        $role_id = session('role_id');
        $login_type = session('login_type');
      
        // For Super Admin, all permissions are true
        if ($login_type === 'Super Admin' || $login_type === 'Company') {
            return [
                'canView' => true,
                'canCreate' => true,
                'canUpdate' => true,
                'canDelete' => true,
                'hasAnyPermission' => true
            ];
        }
        $permissions = [
            'canView' => false,
            'canCreate' => false,
            'canUpdate' => false,
            'canDelete' => false,
            'hasAnyPermission' => false
        ];
        
        return $permissions;
    }

    
    if (!function_exists('get_index_route')) {
        function get_index_route($controller)
        {
            $controllerClass = get_class($controller);
            $router = app(Router::class);
            foreach ($router->getRoutes() as $route) {
                $routeAction = $route->getAction();
                if (isset($routeAction['controller'])) {
                    [$routeController, $routeMethod] = explode('@', $routeAction['controller']);
                    if ($routeMethod === 'index' && $routeController === $controllerClass) {
                        return $route->getName();
                    }
                }
            }
            return null;
        }
    }
    if (!function_exists('check_current_route_permissions')) {
        function check_current_route_permissions($controller)
        {
            $routeName = get_index_route($controller);
            return checkPermissions($routeName);
        }
    }
}


if (!function_exists('get_staff_name')) {
    function get_staff_name($staff_id)
    {
        $staff = DB::table('tbl_staff')->select('staff_name')->where('staff_id', $staff_id)->first();
        return $staff ? $staff->staff_name : "Super Admin";
    }
}
if (!function_exists('permissions_check')) {
    function permissions_check($permission)
    {
        $permissions = checkPermissions(request()->route()->getName());

        // Ensure the input is a string
        if (is_array($permission)) {
            // If accidentally passed as array, take first value or handle accordingly
            $permission = $permission[0] ?? null;
        }

        if ($permission && is_array($permissions) && isset($permissions[$permission])) {
            return $permissions[$permission];
        }

        return false;
    }
}
