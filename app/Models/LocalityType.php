<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalityType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'display_name'];

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
} 