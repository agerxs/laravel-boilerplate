<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingMinutesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'content' => $this->content,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'published_at' => $this->published_at,
            'validation_requested_at' => $this->validation_requested_at,
            'validated_at' => $this->validated_at,
            'validated_by' => $this->validated_by,
            'validation_comments' => $this->validation_comments,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Nouveaux champs pour les résultats des villages
            'people_to_enroll_count' => $this->people_to_enroll_count,
            'people_enrolled_count' => $this->people_enrolled_count,
            'cmu_cards_available_count' => $this->cmu_cards_available_count,
            'cmu_cards_distributed_count' => $this->cmu_cards_distributed_count,
            'complaints_received_count' => $this->complaints_received_count,
            'complaints_processed_count' => $this->complaints_processed_count,
            
            // Calculs automatiques
            'enrollment_rate' => $this->enrollment_rate,
            'cmu_distribution_rate' => $this->cmu_distribution_rate,
            'complaint_processing_rate' => $this->complaint_processing_rate,
            
            // Relations
            'validator' => $this->whenLoaded('validator', function () {
                return [
                    'id' => $this->validator->id,
                    'name' => $this->validator->name,
                    'email' => $this->validator->email,
                ];
            }),
        ];
    }
}
