<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    //
     use HasFactory;

    protected $table = 'tbl_users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'username',
        'email',
        'password',
        'role',          // admin, staff, trainer
        'reference_id',  // links to tbl_trainer.trainer_id when role = trainer
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    protected $hidden = ['password'];

    public function trainer()
    {
        return $this->belongsTo(TrainerModel::class, 'reference_id', 'trainer_id');
    }
}
