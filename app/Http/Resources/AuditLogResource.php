<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends BaseResource
{
    public function data(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'performed_by' => $this->performed_by,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'previous_state' => $this->previous_state,
            'new_state' => $this->new_state,
            'additional_data' => json_decode($this->additional_data),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
