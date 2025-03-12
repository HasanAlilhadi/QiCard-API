<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaLibrary;

class Media extends MediaLibrary
{
    protected $visible = ['preview_url', 'original_url', 'mime_type', 'name', 'size', 'model_type', 'model_id', 'uuid'];
}
