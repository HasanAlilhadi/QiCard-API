<?php

namespace App\Http\Resources;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     *
     */
    public function toArray(Request $request): array
    {
        /** @var File $this */
        return [
            'url' => asset('storage/images/' . $this->file_name),
            'owner_id' => $this->owner_id,
            'created_by' => $this->created_by,
            'file_name' => $this->file_name,
            'type' => $this->type,
            'original_name' => $this->original_name,
            'category' => $this->category,
            'user_id' => $this->created_by,
        ];
    }
}
