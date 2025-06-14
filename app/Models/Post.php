<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable=[
        'user_id',
        'content',
        'media',
        'isNews'
    ];
protected static function booted()
{
    static::deleting(function ($post) {
            $post->reactions()->get()->each->delete();
            $post->comment()->get()->each->delete();
    });
}
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function reactions()
    {
        return $this->hasMany(PostReaction::class);
    }
    public function comment()
    {
        return $this->hasMany(Comment::class);
    }
}
