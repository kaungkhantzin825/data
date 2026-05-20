<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * Always use the central DB for the users table.
     * The middleware may switch the default 'mysql' connection to a tenant DB,
     * but users are NEVER stored in tenant databases.
     */
    protected $connection = 'mysql_central';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'email',
        'profile_logo',
        'tenant_id',
        'plan_id',
        'plan_expired_at',
        'is_active',
        'password',
        'created_by',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'plan_expired_at' => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function staff()
    {
        return $this->hasMany(User::class, 'tenant_id', 'id')->where('id', '!=', $this->id);
    }

    /**
     * The Manager this Staff member is assigned under.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * All Staff members this Manager manages.
     */
    public function managedStaff()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function allRoles()
    {
        return $this->morphToMany(
            \Spatie\Permission\Models\Role::class,
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        );
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }
}
