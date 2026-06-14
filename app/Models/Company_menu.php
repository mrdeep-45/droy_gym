<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company_menu extends Model
{
    use HasFactory;

    protected $table = 'company_menus';

    protected $primaryKey = 'c_menu_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'menu_title',
        'menu_icon',
        'menu_route',
        'status',
        'menu_order',
        'is_submenu',
        'menu_type_id',
        'menu_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_submenu' => 'integer',
    ];


    protected $attributes = [
        'updated_by' => null,
        'menu_order' => null,
    ];


    public function submenus()
    {
        return $this->hasMany(Company_submenu::class, 'c_menu_id');
    }
}
