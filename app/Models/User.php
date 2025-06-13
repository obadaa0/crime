<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Dom\Notation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable ,SoftDeletes;
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'birthday',
        'gender',
        'password',
        'phone',
        'profile_image',
        'bio',
        'role',
        'mother_name',
        'father_name',
        'live',
        'education',
        'work'
    ];
        protected static function booted()
    {
        static::deleting(function ($user) {
            $user->posts()->delete();
            $user->comments()->delete();
            $user->reaction()->delete();
        });
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class,'reporter_id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function friends()
    {
    $friendsAsUser = $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
        ->wherePivot('status', 'accepted')
        ->get();
    $friendsAsFriend = $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')
        ->wherePivot('status', 'accepted')
        ->get();
    return $friendsAsUser->merge($friendsAsFriend)->unique('id');
    }
    public function friendsOf()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }
        public function getAllFriendsAttribute()
    {
        $friendsFromMe = $this->friends()->pluck('users.id')->toArray();
        $friendsToMe = $this->friendsOf()->pluck('users.id')->toArray();

        $allFriendIds = array_unique(array_merge($friendsFromMe, $friendsToMe));

        return User::whereIn('id', $allFriendIds)->get();
    }

    public function getAllFriendsCountAttribute()
    {
        $friendsFromMeCount = $this->friends()->count();
        $friendsToMeCount = $this->friendsOf()->count();

        return $friendsFromMeCount + $friendsToMeCount;
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function block()
    {
        $this->block = 1;
        return $this->save();
    }
    public function Unblock()
    {
        $this->block = 0;
        return $this->save();
    }
    public function reaction()
    {
        return $this->hasMany(PostReaction::class);
    }
    public function reporterPost()
    {
        return $this->hasMany(report_post::class,'reporter');
    }
}
