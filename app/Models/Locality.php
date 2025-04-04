<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Locality extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['name', 'locality_type_id', 'parent_id'];

    protected $table='localite';

    public function type(): BelongsTo
    {
        return $this->belongsTo(LocalityType::class, 'locality_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Locality::class, 'parent_id');
    }

    public function representatives()
    {
        return $this->hasMany(Representative::class, 'locality_id');
    }

    public function localCommittees(): HasMany
    {
        return $this->hasMany(LocalCommittee::class);
    }
}
