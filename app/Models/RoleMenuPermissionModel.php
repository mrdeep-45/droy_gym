<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleMenuPermissionModel extends Model
{
    protected $table = 'role_menu_permissions';

    protected $fillable = [
        'role_id',
        'menu_id',
        'sub_menu_id',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
        'status',
        'created_at',
        'updated_at'
    ];

    public $timestamps = false; // Since you're manually handling timestamps

    public function role()
    {
        return $this->belongsTo(RoleModel::class, 'role_id', 'role_id');
    }


    public function menu()
    {
        return $this->belongsTo(Company_menu::class, 'menu_id', 'c_menu_id');
    }

    public function submenu()
    {
        return $this->belongsTo(Company_submenu::class, 'sub_menu_id', 'c_sub_menu_id');
    }
}
