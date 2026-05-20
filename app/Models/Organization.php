<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'tenant_id'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
