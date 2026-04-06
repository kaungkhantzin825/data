<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    /** Lead data lives in the tenant's private database, configured per-request by the middleware. */
    protected $connection = 'tenant';
    protected $fillable = [
        'uuid', 'tenant_id',
        'business_name', 'contact_name', 'contact_email', 'phone', 'township', 
        'biz_type', 'source', 'weighted', 'potential', 'package', 'plan', 
        'amount', 'status', 'created_by',
        'first_name', 'last_name', 'secondary_contact_number', 'division', 'address',
        'product', 'package_total', 'discount', 'note', 'channel',
        'installation_appointment', 'est_contract_date', 'est_start_date',
        'est_follow_up_date', 'is_referral', 'meeting_note', 'next_step',
        'contracted_date', 'installation_appointment_date', 'customer_note'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
