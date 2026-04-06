<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple Tenant model - no stancl traits needed.
 * We manage database creation manually in the UserObserver.
 */
class Tenant extends Model
{
    protected $table = 'tenants';

    /** Tenants metadata is always in the central DB. */
    protected $connection = 'mysql_central';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'tenancy_db_name',
    ];
}
