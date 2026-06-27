<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    //
    use HasFactory;

    protected $table = 'tbl_payments';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'subscription_id',
        'amount_paid',
        'payment_date',
        'payment_method',
        'transaction_id',
        'payment_status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function subscription()
    {
        return $this->belongsTo(SubscriptionModel::class, 'subscription_id', 'id');
    }
}
