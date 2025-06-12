<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class report_post extends Model
{
    use HasFactory;

    protected $fillable = [
        'reaporter',
        'reported_person',
        'status',
        'post_id',
        'reason'
    ];

    public function reporter(){
        return $this->belongsTo(User::class,'reporter');
    }
    public function reportedPerson(){
        return $this->belongsTo(User::class,'reported_person');
    }
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
    public function makeReviewed()
    {
        return $this->update(['status' => 'reviewed']);
    }
     public function makeRejected()
    {
        return $this->update(['status' => 'rejected']);
    }
   public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }
    public function scopeRejected($query){
        return $query->where('status','rejected');
    }
}
