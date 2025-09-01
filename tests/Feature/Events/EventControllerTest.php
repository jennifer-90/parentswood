<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Event;
use App\Models\CentreInteret;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/*
 *
index (invité → login, user authentifié → OK + props Inertia)
store (création avec image+tags, limite de 10, dates invalides)
edit (interdit si pas créateur / si événement passé)
update (maj champs + envoi mail)
join (doublons, capacité)
toggleParticipation (ajoute puis retire)
show (événement inactif : le créateur non-admin ne voit pas)
deactivate (créateur peut désactiver)
cancel (seul créateur, pas si passé)
report (1x par session)
*/


/**
 * Tests "Feature" pour EventController : on appelle les routes (HTTP)
 * et on vérifie le comportement réel : middlewares, DB, Inertia, mails, etc.
 * Chaque test et helper est expliqué en FR avec des mots simples.
 */
class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * setUp()
     * --------
     * Prépare un environnement propre avant chaque test :
     * - on fige l'heure (pour des tests stables),
     * - on "fake" le disque public (uploads sans toucher le vrai disque),
     * - on met les logs en "spy" (pas de bruit dans la sortie de tests).
     */
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2025-09-01 10:00:00'));
        Storage::fake('public');
        Log::spy();
    }

    /* ====================== Helpers (outils internes aux tests) ======================
     * Idée : créer facilement des utilisateurs, événements, tags, etc.
     * Ils évitent de répéter du code dans chaque test.
     */

    /**
     * creerUtilisateurActif()
     * -----------------------
     * Crée en base un utilisateur "ACTIF" pour qu'il passe le middleware 'active'.
     * But : pouvoir se connecter et accéder aux routes protégées.
     */
    private function creerUtilisateurActif(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_actif' => 1, // IMPORTANT pour passer le middleware 'active'
        ], $overrides));
    }

    /**
     * creerEvenement()
     * ----------------
     * Crée en base un événement "simple" (à venir, non annulé).
     * But : disposer rapidement d'un event prêt à tester (on peut écraser n'importe quel champ).
     */
    private function creerEvenement(array $overrides = []): Event
    {
        $creatorId = $overrides['created_by'] ?? $this->creerUtilisateurActif()->id;

        $base = [
            'name_event'    => 'Event test',
            'description'   => null,
            'date'          => Carbon::now()->addDays(2)->toDateString(), // à venir
            'hour'          => '14:00:00',
            'location'      => 'Namur',
            'min_person'    => 1,
            'max_person'    => 5,
            'picture_event' => null,

            'inactif'       => false,
            'confirmed'     => null,
            'validated_by_id' => null,
            'validated_at'    => null,

            'reports_count' => 0,
            'cancel_note'   => null,
            'cancelled_at'  => null,
            'cancelled_by'  => null,

            'created_by'    => $creatorId,
        ];

        return Event::query()->create(array_merge($base, $overrides));
    }

    /**
     * creerCentreInteret()
     * --------------------
     * Crée un centre d’intérêt avec un nom unique (pour éviter les doublons en DB).
     * But : lier des tags à un événement sans erreur d’unicité.
     */
    private function creerCentreInteret(array $overrides = []): CentreInteret
    {
        $base = ['name' => 'Tag-'.Str::random(8)];
        return CentreInteret::query()->create(array_merge($base, $overrides));
    }

    /**
     * connecter()
     * -----------
     * Connecte un utilisateur (ou en crée un actif si non fourni).
     * But : simuler un utilisateur authentifié pour tester les routes protégées.
     */
    private function connecter(?User $user = null): User
    {
        $user ??= $this->creerUtilisateurActif();
        $this->actingAs($user);
        return $user;
    }

    /* ====================== Auth / accès ====================== */

    /**
     * test_invite_est_redirige_vers_login_sur_events()
     * -------------------------------------------------
     * Ce test vérifie que si on n’est PAS connecté, accéder à /events redirige vers /login.
     * But : s'assurer que la route est bien protégée par "auth".
     */
    public function test_invite_est_redirige_vers_login_sur_events(): void
    {
        $this->get('/events')->assertRedirect('/login');
    }

    /**
     * test_utilisateur_authentifie_voit_page_index_events()
     * -----------------------------------------------------
     * Ce test vérifie que si on est connecté, on obtient bien la page Inertia "Events/Index"
     * avec les props attendues.
     * But : s'assurer que la page se charge et expose les bons jeux de données.
     */
    public function test_utilisateur_authentifie_voit_page_index_events(): void
    {
        $this->connecter();

        $response = $this->get('/events');
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) =>
        $page->component('Events/Index')
            ->has('upcomingEvents')
            ->has('pastEvents')
            ->has('interets')
            ->has('filters')
        );
    }

    /* ====================== store() ====================== */

    /**
     * test_utilisateur_peut_creer_evenement_avec_tags_et_image()
     * -----------------------------------------------------------
     * On envoie un formulaire complet (image + 2 tags).
     * But : vérifier la redirection, le message de succès, la sauvegarde en DB,
     *       l’upload de l’image et la liaison des tags.
     */
    public function test_utilisateur_peut_creer_evenement_avec_tags_et_image(): void
    {
        $user = $this->connecter();

        // Deux tags avec noms uniques
        $tag1 = $this->creerCentreInteret();
        $tag2 = $this->creerCentreInteret();

        // Le contrôleur peut envoyer des mails : on simule pour éviter les vrais envois
        Mail::shouldReceive('raw')->zeroOrMoreTimes();

        $payload = [
            'name_event'      => 'Parc à vélos',
            'description'     => 'Sortie douce',
            'date'            => Carbon::now()->addDays(2)->toDateString(),
            'hour'            => '15:30',
            'location'        => 'Namur',
            'min_person'      => 2,
            'max_person'      => 6,
            'picture_event'   => \Illuminate\Http\UploadedFile::fake()->image('p.jpg', 800, 600),
            'centres_interet' => [$tag1->id, $tag2->id],
        ];

        $response = $this->post('/events', $payload);

        $response->assertRedirect('/events');
        $response->assertSessionHas('flash.success');

        $event = Event::latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame('Parc à vélos', $event->name_event);
        $this->assertSame($user->id, $event->created_by);
        $this->assertEquals('15:30:00', $event->hour); // heure normalisée HH:MM:SS

        // L’image a bien été stockée sur le disque "fake"
        Storage::disk('public')->assertExists($event->picture_event);

        // Les 2 tags sont bien liés
        $this->assertEqualsCanonicalizing(
            [$tag1->id, $tag2->id],
            $event->centresInteret()->pluck('centres_interet.id')->all()
        );
    }

    /**
     * test_store_limite_nombre_evenements_actifs()
     * --------------------------------------------
     * On pré-crée 10 événements à venir pour le même créateur, puis on tente d’en créer un 11e.
     * But : vérifier que la limite métier est respectée (erreur de validation "limit").
     */
    public function test_store_limite_nombre_evenements_actifs(): void
    {
        $user = $this->connecter();

        for ($i = 0; $i < 10; $i++) {
            $this->creerEvenement([
                'created_by' => $user->id,
                'date'       => Carbon::now()->addDay()->toDateString(),
                'hour'       => '12:00:00',
                'inactif'    => false,
                'confirmed'  => null,
            ]);
        }

        $payload = [
            'name_event' => 'Encore un',
            'date'       => Carbon::now()->addDays(3)->toDateString(),
            'hour'       => '10:00',
            'location'   => 'Namur',
            'min_person' => 1,
            'max_person' => 2,
        ];

        $response = $this->from('/events/create')->post('/events', $payload);
        $response->assertRedirect('/events/create');
        $response->assertSessionHasErrors(['limit']);
    }

    /**
     * test_store_refuse_dates_passees_ou_trop_eloignees()
     * ---------------------------------------------------
     * On teste deux cas invalides :
     *  - date passée,
     *  - date au-delà d’un an.
     * But : s’assurer que la validation renvoie bien une erreur sur "date".
     */
    public function test_store_refuse_dates_passees_ou_trop_eloignees(): void
    {
        $this->connecter();

        // Date passée
        $payloadPast = [
            'name_event' => 'Hier…',
            'date'       => Carbon::now()->subDay()->toDateString(),
            'hour'       => '10:00',
            'location'   => 'Namur',
            'min_person' => 1,
            'max_person' => 2,
        ];
        $this->post('/events', $payloadPast)->assertSessionHasErrors(['date']);

        // Date > 1 an
        $payloadTooFar = [
            'name_event' => 'Dans 2 ans',
            'date'       => Carbon::now()->addYears(2)->toDateString(),
            'hour'       => '10:00',
            'location'   => 'Namur',
            'min_person' => 1,
            'max_person' => 2,
        ];
        $this->post('/events', $payloadTooFar)->assertSessionHasErrors(['date']);
    }

    /* ====================== edit()/update() ====================== */

    /**
     * test_edit_interdit_si_pas_createur()
     * ------------------------------------
     * Un utilisateur qui n’est pas le créateur ne peut pas accéder au formulaire d’édition.
     * But : s’assurer que la protection d’autorisation côté contrôleur fonctionne.
     */
    public function test_edit_interdit_si_pas_createur(): void
    {
        $createur = $this->creerUtilisateurActif();
        $this->connecter(); // user courant ≠ créateur

        $event = $this->creerEvenement(['created_by' => $createur->id]);

        $this->get("/events/{$event->id}/edit")
            ->assertSessionHas('flash.error')
            ->assertRedirect(); // back()
    }

    /**
     * test_edit_interdit_si_evenement_passe()
     * ---------------------------------------
     * Même le créateur ne peut pas éditer si l’événement est déjà passé.
     * But : s’assurer qu’on bloque la modif sur un event passé.
     */
    public function test_edit_interdit_si_evenement_passe(): void
    {
        $user = $this->connecter();
        $event = $this->creerEvenement([
            'created_by' => $user->id,
            'date'       => Carbon::now()->subDay()->toDateString(), // passé
            'hour'       => '09:00:00',
        ]);

        $this->get("/events/{$event->id}/edit")
            ->assertSessionHas('flash.error')
            ->assertRedirect();
    }

    /**
     * test_update_sauvegarde_modifs_et_envoie_notifications()
     * -------------------------------------------------------
     * Le créateur modifie plusieurs champs et on s’attend à un envoi de mail aux participants.
     * But : vérifier la persistance des changements + la notification.
     */
    public function test_update_sauvegarde_modifs_et_envoie_notifications(): void
    {
        $user  = $this->connecter();
        $event = $this->creerEvenement(['created_by' => $user->id, 'name_event' => 'Avant']);

        // On ajoute un participant ≠ créateur pour déclencher un mail
        $p = $this->creerUtilisateurActif();
        $event->participants()->syncWithoutDetaching([$p->id]);

        Mail::shouldReceive('raw')->atLeast()->once();

        $payload = [
            'name_event'      => 'Après',
            'description'     => 'maj',
            'date'            => Carbon::now()->addDays(3)->toDateString(),
            'hour'            => '11:45',
            'location'        => 'Jambes',
            'min_person'      => 2,
            'max_person'      => 8,
            'centres_interet' => [],
        ];

        $this->put("/events/{$event->id}", $payload)
            ->assertRedirect("/events/{$event->id}")
            ->assertSessionHas('flash.success');

        $event->refresh();
        $this->assertSame('Après', $event->name_event);
        $this->assertSame('11:45:00', $event->hour);
        $this->assertSame('Jambes', $event->location);
    }

    /* ====================== join()/toggleParticipation() ====================== */

    /**
     * test_join_evite_doublons_et_verifie_capacite()
     * ----------------------------------------------
     * - 1er join : OK,
     * - 2e join du même user : erreur (déjà inscrit),
     * - autre user alors que max=1 : erreur (capacité atteinte).
     * But : valider les garde-fous de participation.
     */
    public function test_join_evite_doublons_et_verifie_capacite(): void
    {
        $user = $this->connecter();

        $event = $this->creerEvenement(['max_person' => 1]);

        $this->post("/events/{$event->id}/join")->assertSessionHas('flash.success');
        $this->post("/events/{$event->id}/join")->assertSessionHas('flash.error');

        $autre = $this->creerUtilisateurActif();
        $this->actingAs($autre)
            ->post("/events/{$event->id}/join")
            ->assertSessionHas('flash.error');

        $this->assertSame(1, $event->participants()->count());
    }

    /**
     * test_toggle_participation_ajoute_puis_retire()
     * ----------------------------------------------
     * 1er appel : ajoute la participation ; 2e appel : la retire.
     * But : vérifier le bon fonctionnement du "toggle".
     */
    public function test_toggle_participation_ajoute_puis_retire(): void
    {
        $user = $this->connecter();
        $event = $this->creerEvenement(['max_person' => 3]);

        $this->post("/events/{$event->id}/toggle-participation")
            ->assertRedirect("/events/{$event->id}")
            ->assertSessionHas('flash.success');
        $this->assertTrue($event->participants()->where('user_id', $user->id)->exists());

        $this->post("/events/{$event->id}/toggle-participation")
            ->assertRedirect("/events/{$event->id}")
            ->assertSessionHas('flash.success');
        $this->assertFalse($event->participants()->where('user_id', $user->id)->exists());
    }

    /* ====================== show() ====================== */

    /**
     * Si l’événement est inactif, le créateur (qui n’est PAS admin)
     * ne doit pas pouvoir voir la page "show" → redirection /events + flash error.
     *
     * Contexte spécifique du projet :
     *  - 1er utilisateur créé = Super-admin
     *  - 2e utilisateur créé = Admin
     *  - à partir du 3e = User “normal”
     */
    public function test_show_cache_evenement_inactif_si_createur_non_admin(): void
    {
        // 1) On "consomme" les deux premières créations d'utilisateurs,
        //    pour laisser les rôles Super-admin & Admin aux #1 et #2.
        $first  = $this->creerUtilisateurActif(); // deviendra Super-admin dans ton appli
        $second = $this->creerUtilisateurActif(); // deviendra Admin dans ton appli

        // 2) Le 3e utilisateur sera donc un "User" normal (non admin)
        $createur = $this->creerUtilisateurActif();

        // Par sécurité (si Spatie/Permission est installé) :
        // on enlève explicitement tout rôle au créateur, puis on vérifie.
        if (method_exists($createur, 'syncRoles')) {
            $createur->syncRoles([]);
            // Si le cache Spatie existe, on le vide.
            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }
            $createur->refresh();
            $this->assertFalse(
                $createur->hasAnyRole(['Admin', 'Super-admin']),
                'Le créateur ne doit pas être Admin/Super-admin dans ce test.'
            );
        }

        // 3) On se connecte en tant que créateur (non admin)
        $this->actingAs($createur);

        // 4) On crée un event appartenant à ce créateur,
        //    puis on le rend inactif pour tester la règle d’accès.
        $event = $this->creerEvenement([
            'inactif'    => false,
            'created_by' => $createur->id,
        ]);
        $event->update(['inactif' => true]);
        $event->refresh();

        // Sanity check : s'assurer qu'on teste bien un event inactif
        $this->assertTrue((bool) $event->inactif, 'Sanity check : inactif doit être true');

        // 5) Attendu : le créateur NON admin n’a pas accès → redirection /events + flash error
        $this->get("/events/{$event->id}")
            ->assertRedirect('/events')
            ->assertSessionHas('flash.error');
    }


    /* ====================== deactivate()/cancel() ====================== */

    /**
     * test_createur_peut_desactiver_evenement()
     * -----------------------------------------
     * Le créateur peut désactiver son événement.
     * On ajoute un participant ≠ créateur pour forcer l’envoi de mail (notification).
     * But : vérifier que la désactivation marche et qu’un mail est envoyé.
     */
    public function test_createur_peut_desactiver_evenement(): void
    {
        $createur = $this->connecter();
        $event = $this->creerEvenement(['created_by' => $createur->id]);

        // Ajout d’un destinataire réel (pour que notifyParticipantsInline envoie un mail)
        $autre = $this->creerUtilisateurActif();
        $event->participants()->syncWithoutDetaching([$autre->id]);

        Mail::shouldReceive('raw')->atLeast()->once();

        $this->put("/events/{$event->id}/deactivate")
            ->assertRedirect('/events')
            ->assertSessionHas('flash.success');

        $event->refresh();
        $this->assertTrue((bool) $event->inactif);
    }

    /**
     * test_seul_createur_peut_annuler_et_pas_si_passe()
     * -------------------------------------------------
     * - Un autre utilisateur ne peut pas annuler,
     * - Le créateur peut annuler (on force un mail avec un participant),
     * - On ne peut PAS annuler un événement déjà passé.
     * But : vérifier les règles d’annulation.
     */
    public function test_seul_createur_peut_annuler_et_pas_si_passe(): void
    {
        $createur = $this->creerUtilisateurActif();
        $event = $this->creerEvenement(['created_by' => $createur->id]);

        // Un autre user tente : non autorisé
        $autre = $this->connecter();
        $this->post("/events/{$event->id}/cancel")->assertSessionHas('flash.error');

        // Le créateur annule (on garantit un destinataire mail)
        $this->actingAs($createur);
        $participant = $this->creerUtilisateurActif();
        $event->participants()->syncWithoutDetaching([$participant->id]);

        Mail::shouldReceive('raw')->atLeast()->once();

        $this->post("/events/{$event->id}/cancel")
            ->assertSessionHas('flash.success');

        $event->refresh();
        $this->assertTrue((bool) $event->inactif);
        $this->assertNotNull($event->cancelled_at);

        // Cas "événement passé" : annulation interdite
        $past = $this->creerEvenement([
            'created_by' => $createur->id,
            'date'       => Carbon::now()->subDay()->toDateString(),
            'hour'       => '08:00:00',
            'inactif'    => false,
            'cancelled_at' => null,
        ]);
        $this->post("/events/{$past->id}/cancel")->assertSessionHas('flash.error');
    }

    /* ====================== report() ====================== */

    /**
     * test_report_incremente_une_fois_par_session()
     * ---------------------------------------------
     * Lorsqu’un user clique deux fois sur "signaler", le compteur ne doit
     * s’incrémenter qu’une seule fois dans la même session.
     * But : empêcher le spam de signalements.
     */
    public function test_report_incremente_une_fois_par_session(): void
    {
        $this->connecter();
        $event = $this->creerEvenement();

        // 1er signalement : +1
        $this->post("/events/{$event->id}/report")->assertSessionHas('flash.success');
        $event->refresh();
        $this->assertSame(1, (int) $event->reports_count);

        // 2e dans la même session : pas d’augmentation
        $this->post("/events/{$event->id}/report")->assertSessionHas('flash.info');
        $event->refresh();
        $this->assertSame(1, (int) $event->reports_count);
    }
}
