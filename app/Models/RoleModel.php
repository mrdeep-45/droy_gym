<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_role';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_id',
        'role_name',
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];
}
