<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'meeting_rate',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'meeting_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Obtenir le taux actif pour un rôle spécifique
     */
    public static function getActiveRateForRole(string $role)
    {
        return self::where('role', $role)
            ->where('is_active', true)
            ->first();
    }
} 