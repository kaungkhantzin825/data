<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /** Plans are always in the central DB. */
    protected $connection = 'mysql_central';

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
