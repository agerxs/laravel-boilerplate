<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocalityType extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['libelle'];

    protected $table='locality_type';

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
