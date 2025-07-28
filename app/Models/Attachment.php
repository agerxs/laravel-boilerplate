<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'meeting_id',
        'title',
        'original_name',
        'file_path',
        'file_type',
        'nature',
        'size',
        'uploaded_by',
        'nature_label',
    ];

    protected $appends = ['full_file_path'];

    public function getFullFilePathAttribute()
    {
        if (empty($this->file_path)) {
            return null;
        }

        return url('storage/' . $this->file_path);
    }

    public function getNatureLabelAttribute()
    {
        return match($this->nature) {
            'photo' => 'Photo',
            'document_justificatif' => 'Document justificatif',
            'compte_rendu' => 'Compte rendu',
            default => $this->nature
        };
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
} 