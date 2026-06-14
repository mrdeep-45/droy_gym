@php

use App\Models\Company_submenu;
use App\Models\Company_menu;
$menuTree = [];

if (session()->get('login_type') == 'Company') {
    $companyId = session()->get('company_id');
    $permissions = DB::table('company_permission')->where('company_id', $companyId)->get();

    $menuIds = $permissions->pluck('c_menu_id')->unique()->toArray();
    $submenuIds = $permissions->pluck('c_sub_menu_id')->filter()->unique()->toArray();

    $menus = Company_menu::whereIn('c_menu_id', $menuIds)->orderBy('menu_order')->get()->keyBy('c_menu_id');

    $submenus = Company_submenu::whereIn('c_sub_menu_id', $submenuIds)
        ->where('status', 0) // Add this line
        ->orderBy('submenu_order')
        ->get()
        ->groupBy('c_menu_id');

    foreach ($menus as $menuId => $menu) {
        $menuTree[] = [
            'id' => $menu->c_menu_id,
            'name' => $menu->menu_title,
            'icon' => $menu->menu_icon ?? 'bx bx-cube',
            'route' => $menu->menu_route,
            'has_submenu' => $menu->is_submenu == 1,
            'submenus' => $submenus->get($menuId) ?? [],
        ];
    }
}

if (session()->get('login_type') == 'Staff') {
    $roleId = session()->get('role_id');
    $permissions = DB::table('role_menu_permissions')->where('role_id', $roleId)->get();

    $menuIds = $permissions->pluck('menu_id')->unique()->toArray();
    $submenuIds = $permissions->pluck('sub_menu_id')->filter()->unique()->toArray();

    $menus = Company_menu::whereIn('c_menu_id', $menuIds)->orderBy('menu_order')->get()->keyBy('c_menu_id');

    $submenus = Company_submenu::whereIn('c_sub_menu_id', $submenuIds)
        ->orderBy('submenu_order')
        ->get()
        ->groupBy('c_menu_id');

    foreach ($menus as $menuId => $menu) {
        $menuTree[] = [
            'id' => $menu->c_menu_id,
            'name' => $menu->menu_title,
            'icon' => $menu->menu_icon ?? 'bx bx-cube',
            'route' => $menu->menu_route,
            'has_submenu' => $menu->is_submenu == 1,
            'submenus' => $submenus->get($menuId) ?? [],
        ];
    }
    // dd($menuTree)->toArray();
}

@endphp
<aside class="app-sidebar sticky" id="sidebar">
    @php
$company_data = get_company_details(getCreatedBy());
$userLogo = $company_data && $company_data->logo ? $company_data->logo : null;
$Alt = $company_data && $company_data->name ? $company_data->name : null;
$logoUrl = $userLogo
    ? $actual_url . '/uploads/company/' . $userLogo
    : $actual_url . '/admin_assets/images/brand-logos/enli-logo.png';
$loginType = session('login_type');
$dashboardUrl =
    $loginType == 'Company' || $loginType == 'Staff' ? route('company/dashboard') : route('dashboard');
    @endphp

    <div class="main-sidebar-header">
        <a href="{{ $dashboardUrl }}" class="header-logo">
            <img src="{{ $logoUrl }}" style="width:125px;height:33px;" alt="{{ $Alt }}" class="desktop-logo">
            <img src="{{ $logoUrl }}" style="width:38px;height:33px;" alt="{{ $Alt }}" class="toggle-logo">
            <img src="{{ $logoUrl }}" style="width:125px;height:33px;" alt="{{ $Alt }}" class="desktop-dark">
            <img src="{{ $logoUrl }}" style="width:38px;height:33px;" alt="{{ $Alt }}" class="toggle-dark">
        </a>
    </div>

    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                </svg>
            </div>
            <ul class="main-menu">
                <li class="slide__category"><span class="category-name">Main</span></li>
                @if (session()->get('login_type') == 'Super Admin')
                <li class="slide">
                    <a href="{{ route('dashboard') }}" class="side-menu__item {{ request()->is('dashboard') ? 'active' : '' }}">
                        <span class=" side-menu__icon">
                            <i class='bx bx-desktop'></i>
                        </span>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>
                <li class="slide has-sub {{ request()->is('company') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('company') ? 'active' : '' }}">
                        <span class=" side-menu__icon">
                            <i class='bx bx-cube'></i>
                        </span>
                        <span class="side-menu__label">Master</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Master</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('company') }}" class="side-menu__item {{ request()->is('company') ? 'active' : '' }}">Company
                                Register</a>
                        </li>
                    </ul>
                </li>
                <li class="slide has-sub {{ request()->is('company/role') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('company/role') ? 'active' : '' }}">
                        <span class=" side-menu__icon">
                            <i class="bi bi-gear-wide-connected"></i>

                        </span>
                        <span class="side-menu__label">Settings</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Company Menu</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('company/role') }}" class="side-menu__item {{ request()->is('company/role') ? 'active' : '' }}">Company
                                Menu</a>
                        </li>
                    </ul>
                </li>
                @endif
                @if (session()->get('login_type') == 'Company' || session()->get('login_type') == 'Staff')

                    @foreach ($menuTree as $menu)
                                @php
                                    $hasRoute = Route::has($menu['route']);
                                    $isActive = $hasRoute && (request()->is($menu['route'] . '*') || request()->routeIs($menu['route']));

                                    if (!$isActive && isset($menu['submenus'])) {
                                        foreach ($menu['submenus'] as $submenu) {
                                            if (Route::has($submenu->sub_menu_route) && request()->routeIs($submenu->sub_menu_route)) {
                                                $isActive = true;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                @if ($menu['has_submenu'])
                                <li class="slide has-sub {{ $isActive ? 'open' : '' }}">
                                    <a href="javascript:void(0);" class="side-menu__item {{ $isActive ? 'active' : '' }}">
                                        <span class="side-menu__icon">
                                            <i class="{{ $menu['icon'] }}"></i>
                                        </span>
                                        <span class="side-menu__label">{{ $menu['name'] }}</span>
                                        <i class="fe fe-chevron-right side-menu__angle"></i>
                                    </a>
                                    <ul class="slide-menu child1">
                                        @foreach ($menu['submenus'] as $submenu)
                                        @php
                        $subRouteExists = Route::has($submenu->sub_menu_route);
                                        @endphp
                                        <li class="slide">
                                            <a href="{{ $subRouteExists ? route($submenu->sub_menu_route) : '#' }}" class="side-menu__item {{ $subRouteExists && request()->routeIs($submenu->sub_menu_route) ? 'active' : '' }}">
                                                {{ $submenu->sub_menu_title }}
                                            </a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @else
                                <li class="slide">
                                    <a href="{{ $hasRoute ? route($menu['route']) : '#' }}" class="side-menu__item {{ $isActive ? 'active' : '' }}">
                                        <span class="side-menu__icon">
                                            <i class="{{ $menu['icon'] }}"></i>
                                        </span>
                                        <span class="side-menu__label">{{ $menu['name'] }}</span>
                                    </a>
                                </li>
                                @endif
                    @endforeach
                @endif
                @if (session()->get('login_type') == 'Company')
                <li class="slide has-sub {{ request()->is('menu/submenu') || request()->is('profile') || request()->is('qtyset') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <span class="side-menu__icon">
                            <i class='bx bx-cube'></i>
                        </span>
                        <span class="side-menu__label">Settings</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Settings</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('menu/submenu') }}" class="side-menu__item {{ request()->is('menu/submenu') ? 'active' : '' }}">Role
                                Permission</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('profile') }}" class="side-menu__item {{ request()->is('profile') ? 'active' : '' }}">Profile</a>
                        </li>

                        <li class="slide d-none">
                            <a href="{{ route('qtyset') }}" class="side-menu__item {{ request()->is('qtyset') ? 'active' : '' }}">Reorder
                                Alert</a>
                        </li>
                    </ul>
                </li>
                @endif

                <li class="slide">
                    <a href="{{ route('logout') }}" class="side-menu__item {{ request()->is('logout') ? 'active' : '' }}">
                        <span class=" side-menu__icon">
                            <i class='bi bi-box-arrow-left'></i>
                        </span>
                        <span class="side-menu__label">Logout</span>
                    </a>
                </li>
            </ul>
            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                </svg>
            </div>
        </nav>
    </div>
</aside>

<style>
    .modal-content {
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .avatar {
        font-weight: 600;
    }

    .hover-item {
        cursor: pointer;
        transition: all 0.2s;
    }

    .hover-item:hover {
        background-color: rgba(0, 0, 0, 0.03);
        transform: translateY(-1px);
    }

    .text-purple {
        color: #6f42c1;
    }

    .rounded-3 {
        border-radius: 8px !important;
    }

</style>

<div class="modal fade" id="callReminderModal" tabindex="-1" aria-labelledby="callReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <div class="d-flex align-items-center w-100">
                    <i class="fas fa-phone-alt me-2"></i>
                    <h6 class="modal-title mb-0 flex-grow-1">Call Reminder </h6>

                    <button type="button" class="btn-close btn-close-white m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-0" id="callReminderContent">
                <!-- Content will be inserted here -->
            </div>

        </div>
    </div>
</div>
