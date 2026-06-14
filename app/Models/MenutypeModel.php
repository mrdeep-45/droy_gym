<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenutypeModel extends Model
{
    use HasFactory;

    protected $table = 'menu_type';
    protected $primaryKey = 'menu_type_id';
    public $timestamps = false;
    const CREATED_AT = null;
    const UPDATED_AT = null;
    protected $fillable = [
        'menu_type_id',
        'type_name',
    ];
}
