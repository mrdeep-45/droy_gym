<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietWorkoutPlanModel extends Model
{
    //
     use HasFactory;

    protected $table = 'tbl_workout_diet_plans';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'member_id',
        'trainer_id',
        'start_date',
        'end_date',
        'workout_details',
        'diet_details',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    protected $casts = [
        'workout_details' => 'array',
        'diet_details'    => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(MemberModel::class, 'member_id', 'id');
    }

    public function trainer()
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id', 'trainer_id');
    }
}
