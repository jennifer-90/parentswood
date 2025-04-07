<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * Les colonnes qui peuvent être modifiées via des formulaires ou scripts.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Relation avec les utilisateurs (plusieurs utilisateurs peuvent avoir plusieurs rôles).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }
}
