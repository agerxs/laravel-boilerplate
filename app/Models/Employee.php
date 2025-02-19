<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'cni_number',
        'structure_id',
        'job_title_id',
        'contract_type_id',
        'assignment_post_id',
        'phone',
        'whatsapp'
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function assignmentPost(): BelongsTo
    {
        return $this->belongsTo(AssignmentPost::class);
    }
} 