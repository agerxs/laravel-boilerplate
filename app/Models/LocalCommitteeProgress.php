<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalCommitteeProgress extends Model
{
    use HasFactory;
    
    protected $table = 'local_committee_progress';
    
    protected $fillable = [
        'user_id',
        'name',
        'locality_id',
        'status',
        'form_data',
        'files',
        'last_active_step',
    ];
    
    protected $casts = [
        'form_data' => 'array',
        'files' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function locality()
    {
        return $this->belongsTo(Locality::class);
    }
} 