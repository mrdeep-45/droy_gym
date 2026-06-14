<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'company_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_no',
        'logo',
        'favicon',
        'project_name',
        'registration_number',
        'company_type',
        'industry',
        'founded_date',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'tax_id',
        'description',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'founded_date' => 'date',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 0,
    ];
    public function permissions()
    {
        return $this->hasMany(Company_permission_Model::class, 'company_id', 'company_id');
    }
}
