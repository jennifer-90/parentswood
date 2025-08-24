<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentreInteret extends Model
{
    protected $table = 'centres_interet';

    protected $fillable = ['name'];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'centre_interet_event', 'centre_interet_id', 'event_id')
            ->withTimestamps();
    }
}
