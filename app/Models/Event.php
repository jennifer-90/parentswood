<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Message;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [ /* L’ordre des colonnes dans $fillable n’a aucune importance pour Laravel */
        'name_event',
        'description',
        'date',
        'hour',
        'location',
        'min_person',
        'max_person',
        'created_by',
        'inactif',
        'confirmed',
        'validated_by_id',
        'validated_at',

        'reports_count',

        'cancel_note',
        'cancelled_at',
        'cancelled_by',

        'picture_event',
    ];

    protected $casts = [
        'date'         => 'date',
        'validated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'inactif'      => 'boolean',
        'confirmed'    => 'boolean',
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

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }


}
