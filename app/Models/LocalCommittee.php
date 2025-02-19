<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalCommittee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'locality_id',
        'president_id',
        'installation_date',
        'ano_validation_date',
        'fund_transmission_date',
        'villages_count',
        'population_rgph',
        'population_to_enroll',
        'status'
    ];

    protected $casts = [
        'installation_date' => 'date',
        'ano_validation_date' => 'date',
        'fund_transmission_date' => 'date',
    ];

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function president(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LocalCommitteeMember::class);
    }

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class)
            ->withTimestamps();
    }
} 