<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'text',
    ];

    // Auteur du message
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // Événement concerné
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
