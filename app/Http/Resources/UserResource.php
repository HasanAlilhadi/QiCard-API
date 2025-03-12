<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function data(Request $request): array
    {
        /** @var User $this */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'created_at' => $this->created_at,
        ];
    }

    public function relations(Request $request): array
    {
        return [
            'channels' => $this->whenLoaded('channels'),
            'channel' => $this->whenLoaded('channel'),
            'roles' => $this->whenLoaded('roles'),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }

    public function appends(Request $request): array
    {
        return [
            'image' => new MediaResource($this->whenLoaded('image'))
        ];
    }
}
