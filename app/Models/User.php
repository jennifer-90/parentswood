<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use Illuminate\Support\Facades\Storage;


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
        'self_deactivated'    => 'boolean',
    ];




    protected $appends = ['picture_profil_url'];

    public function getPictureProfilUrlAttribute(): ?string
    {
        return $this->picture_profil
            ? Storage::url($this->picture_profil)
            : null;
    }

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

    /**
     * Les événements créés par l'utilisateur
     */
    public function eventsCreated()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }


    // Les personnes que JE bloque
    public function blocks()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user1_id', 'user2_id')
            ->withTimestamps();
    }

// Les personnes qui ME bloquent
    public function blockedBy()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user2_id', 'user1_id')
            ->withTimestamps();
    }

    public function hasBlocked(User $other): bool
    {
        return $this->blocks()->where('users.id', $other->id)->exists();
    }

    public function isBlockedBy(User $other): bool
    {
        return $this->blockedBy()->where('users.id', $other->id)->exists();
    }


    public function getRouteKeyName(): string
    {
        return 'pseudo';
    }




}
