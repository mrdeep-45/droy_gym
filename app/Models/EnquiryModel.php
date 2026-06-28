<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquiryModel extends Model
{
    //
     use HasFactory;

    protected $table = 'tbl_enquiries';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'full_name',
        'phone',
        'email',
        'enquiry_date',
        'source',
        'status',
        'follow_up_date',
        'remarks',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];
}
