<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
                $model->created_by = request()->user()->id;
        });
    }

}
