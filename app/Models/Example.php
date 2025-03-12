<?php

namespace App\Models;

use App\Traits\VuexyTrait;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Example extends Authenticatable implements JWTSubject, HasMedia
{
    use HasRoles, SoftDeletes, InteractsWithMedia, VuexyTrait;

    protected array $exportHeadings = [
        'id',
        'name',
        'username',
        'channels.channel_name',
        'permissions.name',
        'created_at'
    ];

    protected array $exportMappings = [
        'id',
        'name',
        'username',
        'channels.channel_name',
        'permissions.name',
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
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/jpg',
                'image/png',
            ])
            ->singleFile();

//        $this->addMediaCollection('additional_images')
//            ->acceptsMimeTypes([
//                'image/jpeg',
//                'image/jpeg',
//                'image/png',
//            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->width(368)
            ->height(232)
            ->performOnCollections( 'image');

//            ->performOnCollections('additional_images', 'image');  // Put this if another collection exists
    }

    public function image(): MorphOne
    {
        return $this->morphOne($this->getMediaModel(), 'model')
            ->where('collection_name', '=', 'image')->latest();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
