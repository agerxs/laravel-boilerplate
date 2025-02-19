<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentLocation extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'locality_id'];

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
} 