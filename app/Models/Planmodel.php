<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planmodel extends Model
{
    //
    use HasFactory;

    protected $table = 'tbl_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_id',
        'plan_name',
        'duration',
        'price',
        'description',
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];
}
