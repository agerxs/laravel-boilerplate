<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaItem extends Model
{
    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'duration_minutes',
        'order',
        'status',
        'presenter_id'
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function presenter()
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }

    public function notes()
    {
        return $this->hasMany(AgendaItemNote::class);
    }

    // Méthode pour réorganiser l'ordre des points
    public function moveOrder(int $newOrder)
    {
        $oldOrder = $this->order;
        
        if ($newOrder > $oldOrder) {
            static::where('meeting_id', $this->meeting_id)
                ->whereBetween('order', [$oldOrder + 1, $newOrder])
                ->decrement('order');
        } else {
            static::where('meeting_id', $this->meeting_id)
                ->whereBetween('order', [$newOrder, $oldOrder - 1])
                ->increment('order');
        }

        $this->update(['order' => $newOrder]);
    }
} 