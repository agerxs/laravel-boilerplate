<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HierarchyLocality extends Model
{
    use SoftDeletes;

    protected $fillable = ['level', 'name', 'is_active'];

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
