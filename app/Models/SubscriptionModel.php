<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionModel extends Model
{
    //
     use HasFactory;

    protected $table = 'tbl_subscriptions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'member_id',
        'plan_id',
        'trainer_id',
        'start_date',
        'end_date',
        'amount_payable',
        'status',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function member()
    {
        return $this->belongsTo(MemberModel::class, 'member_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Planmodel::class, 'plan_id', 'plan_id');
    }

    public function trainer()
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id', 'trainer_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentModel::class, 'subscription_id', 'id');
    }

}
