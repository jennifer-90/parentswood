<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Storage;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => function () use ($request) {
                    $user = $request->user();
                    if (!$user) return null;

                    return [
                        'id'             => $user->id,
                        'first_name'     => $user->first_name,
                        'last_name'      => $user->last_name,
                        'pseudo'         => $user->pseudo,
                        'email'          => $user->email,
                        'roles'          => $user->roles->pluck('name')->toArray(),
                        'picture_profil' => $user->picture_profil,
                        // URL utilisable directement par <img src="...">
                        'picture_profil_url'  => $user->picture_profil ? \Storage::disk('public')->url($user->picture_profil) : null,
                    ];
                },
            ],

            // ✅ Normalisation des messages flash (compat ->with('flash', [...]) ET ->with('success', ...))
            'flash' => function () use ($request) {
                $session = $request->session();

                // ⬇️ `$bag` = ce que tu aurais mis via ->with('flash', ['success' => '...'])
                $bag = (array) $session->get('flash', []);

                // ⬇️ on renvoie un tableau standardisé : success/error/info
                //    priorité aux clés du “bag” puis aux clés simples (success/error/status/info)
                return [
                    'success' => $bag['success']
                        ?? $session->get('success')
                            ?? $session->get('status') // ex. certains contrôleurs mettent 'status'
                            ?? null,

                    'error'   => $bag['error']
                        ?? $session->get('error')
                            ?? null,

                    'info'    => $bag['info']
                        ?? $session->get('info')
                            ?? null,
                ];
            },

            'totalUsers' => fn () => \App\Models\User::count(),

            //'csrf_token' => csrf_token(),

        ];
    }

}
