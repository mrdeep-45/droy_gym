<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SubmenuModal extends Model
{
    use HasFactory;

    protected $table = 'submenus';

    protected $primaryKey = 'sub_menu_id';
    protected $fillable = [
        'sub_menu_id',
        'menu_id',
        'sub_menu_title',
        'sub_menu_icon',
        'sub_menu_route',
        'status',
        'submenu_order',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by'
    ];

    public function menu()
    {
        return $this->belongsTo(Menu_settingModal::class, 'menu_id');
    }
}
