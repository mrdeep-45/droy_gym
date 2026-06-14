<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerModel extends Model
{
    //
    use HasFactory;

    protected $table = 'tbl_trainer';
    protected $primaryKey = 'trainer_id';

    protected $fillable = [
        'trainer_id',
        'trainer_name',
        'trainer_phone',
        'specialization',
        't_photo',
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];
}
