<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;

class MediaResource extends BaseResource
{
    public function data(Request $request): array
    {
        /** @var Media $this */
        return [
            'id' => $this->uuid,
            'preview_url' => $this->preview_url,
            'original_url' => $this->original_url,
            'mime_type' => $this->mime_type,
            'name' => $this->name,
            'size' => $this->size,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
        ];

    }
}
