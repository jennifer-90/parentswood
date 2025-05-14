<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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
        'report',
        'picture_event',
        'confirmed',
        'validated_by_id',
        'validated_at',
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


}
