<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'meeting'           => [
                'id'    => $this->meeting->id,
                'title' => $this->meeting->title,
            ],
            'status'            => $this->status,
            'total_amount'      => $this->total_amount,
            'participants_count'=> $this->participants_count,
            'created_at'        => $this->created_at,
            'items'             => PaymentItemResource::collection($this->whenLoaded('items')),
        ];
    }
} 