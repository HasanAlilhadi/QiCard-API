<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class BaseModel extends Model
{
    protected static function boot(): void
    {
        parent::boot();
        self::creating(function ($model){
            if (App::runningInConsole())
                $model->created_by = 1;
            else
                $model->created_by = auth()->user()->id;
        });

        self::updating(function ($model){
            if (App::runningInConsole())
                $model->updated_by = 1;
            else
                $model->updated_by = auth()->user()->id;
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
