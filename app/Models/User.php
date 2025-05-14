<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'pseudo',
        'last_name',
        'first_name',
        'genre',
        'email',
        'password',
        'localisation',
        'picture_profil',
        'privacy_status',
        'is_actif',
        'max_create_event',
        'anonyme',
        'centre_interet',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_actif' => 'boolean',
        'anonyme' => 'boolean',
    ];

    /**
     * Relation avec les rôles (relation plusieurs à plusieurs).
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Vérifie si l'utilisateur possède un rôle spécifique.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Attribue un rôle à l'utilisateur.
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        if (!$this->roles->contains('id', $role->id)) {
            $this->roles()->attach($role);
        }
    }

    /**
     * Attribue automatiquement un rôle à l'utilisateur lors de la création via le formulaire.
     */


    public function hasAnyRole($roles): bool
    {
        if (is_array($roles)) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles->where('name', $roles)->isNotEmpty();
    }



    protected static function booted()
    {
        static::created(function ($user) {
            // Compte le nombre total d'utilisateurs
            $userCount = User::count();

            if ($userCount == 1) {
                // Premier utilisateur → Super-admin
                $user->assignRole('Super-admin');
            } elseif ($userCount == 2) {
                // Deuxième utilisateur → Admin
                $user->assignRole('Admin');
            } else {
                // Tous les autres → User
                $user->assignRole('User');
            }
        });
    }

    public function eventsParticipated()
    {
        return $this->belongsToMany(Event::class, 'event_user')->withTimestamps();
    }

    // Deux users puisse communiuquer entre eux si ils ont un évent en commun
    public function canMessageWith(User $otherUser): bool
    {
        return $this->eventsParticipated()
            ->whereHas('participants', function ($q) use ($otherUser) {
                $q->where('user_id', $otherUser->id);
            })->exists();
    }

}
