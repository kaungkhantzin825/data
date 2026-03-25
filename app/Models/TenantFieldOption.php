<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantFieldOption extends Model
{
    use HasFactory, \App\Traits\BelongsToTenant;

    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
