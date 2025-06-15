<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $fillable = [
        'reporter_id',
        'description',
        'status',
        'media',
        'crime_type',
        'location'
    ];

    public function User()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
    public function progress()
    {
        return $this->update(['status' => 'progress']);
    }
    public function resolved()
    {
        return $this->update(['status' => 'resolved']);
    }
}
