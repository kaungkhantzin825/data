<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantFieldOption extends Model
{
    use HasFactory;

    /** Tenant field options live in the tenant's private database. */
    protected $connection = 'tenant';

    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
