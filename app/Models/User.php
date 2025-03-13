<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, SoftDeletes;

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

        self::deleting(function ($model){
            if (App::runningInConsole())
                $model->deleted_by = 1;
            else
                $model->deleted_by = auth()->user()->id;
        });
    }

    protected array $exportHeadings = [
        'id',
        'name',
        'username',
        'created_at'
    ];

    protected array $exportMappings = [
        'id',
        'name',
        'username',
        'created_at'
    ];

    protected $fillable = [
        'name',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function destroyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
