<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_event',
        'description',
        'date',
        'hour',
        'location',
        'min_person',
        'max_person',
        'created_by',
        'inactif',
        'report',
        'picture_event',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_user')->withTimestamps();
    }

    public function messages() // Les commentaires
    {
        return $this->hasMany(Message::class);
    }


}
