<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Company_submenu extends Model
{
    use HasFactory;

    protected $table = 'company_submenus';

    protected $primaryKey = 'c_sub_menu_id';
    protected $fillable = [
        'c_sub_menu_id',
        'c_menu_id',
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
        return $this->belongsTo(Company_menu::class, 'c_menu_id');
    }
}
