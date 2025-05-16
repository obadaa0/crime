<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'sender_id',
        'type',
        'data',
        'is_read'
    ];
    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
        return $this;
    }
    public function senderName()
    {
        return $this->sender->firstname . " " . $this->sender->lastname;
    }
}
