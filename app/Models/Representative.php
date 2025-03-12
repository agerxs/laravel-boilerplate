<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Representative extends Model
{
    use HasFactory;

    protected $fillable = [
        'locality_id',
        'local_committee_id',
        'first_name',
        'last_name',
        'phone',
        'role'
    ];

    // Définir la relation inverse avec la localité
    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function localCommittee(): BelongsTo
    {
        return $this->belongsTo(LocalCommittee::class);
    }
} 