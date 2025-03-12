<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class File extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'file_name',
        'original_name',
        'type',
        'size',
        'category',
    ];

    public function fileable()
    {
        return $this->morphTo();
    }

}
