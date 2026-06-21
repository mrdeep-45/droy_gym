<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberModel extends Model
{
    //
    use HasFactory;

    protected $table = 'tbl_members';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'member_number',
        'full_name',
        'email',
        'phone',
        'gender',
        'dob',
        'joining_date',
        'm_photo',
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];
}
