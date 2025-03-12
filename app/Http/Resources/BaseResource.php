<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class BaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = array_merge($this->data($request), $this->appends($request), $this->relations($request));

        return $this->removeNullValues($data);
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function appends(Request $request): array
    {
        return [];
    }

    public function relations(Request $request): array
    {
        return [];
    }

    public function removeNullValues(array $data): array
    {
        $filtered_data = [];
        foreach ($data as $key => $value) {
            if ($value instanceof JsonResource and $value->resource === null) {
                continue;
            }
            $filtered_data[$key] = $this->whenNotNull($value);
        }

        return $filtered_data;
    }

}
