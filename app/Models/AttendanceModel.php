<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceModel extends Model
{
    //
    use HasFactory;

    protected $table = 'tbl_attendance';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'member_id',
        'check_in',
        'check_out',
        'created_at',
        'status',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function member()
    {
        return $this->belongsTo(MemberModel::class, 'member_id', 'id');
    }
}
