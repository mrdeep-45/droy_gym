<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseModel extends Model
{
    //
        use HasFactory;

    protected $table = 'tbl_expenses';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'title',
        'category',
        'amount',
        'expense_date',
        'paid_to',
        'note',
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];
}
