<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company_permission_Model extends Model
{
    use HasFactory;

    protected $table = 'company_permission';

    protected $primaryKey = 'cp_id';

    protected $fillable = [
        'company_id',
        'c_menu_id',
        'c_sub_menu_id',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'update_at'
    ];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function company()
    {
        return $this->belongsTo(CompanyModel::class, 'company_id', 'company_id');
    }

    public function menu()
    {
        return $this->belongsTo(Company_menu::class, 'c_menu_id', 'c_menu_id');
    }

    public function submenu()
    {
        return $this->belongsTo(Company_submenu::class, 'c_sub_menu_id', 'c_sub_menu_id');
    }
}
