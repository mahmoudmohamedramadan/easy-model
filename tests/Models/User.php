<?php

namespace Ramadan\EasyModel\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    public static int $updatedCount = 0;

    protected $guarded = [];

    protected $casts = [
        'is_admin'  => 'boolean',
        'published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updated(function () {
            static::$updatedCount++;
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(Comment::class, Post::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
