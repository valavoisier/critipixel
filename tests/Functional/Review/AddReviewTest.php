<?php

declare(strict_types=1);

namespace App\Tests\Functional\Review;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;

final class AddReviewTest extends FunctionalTestCase
{
    /**
     * Test fonctionnel de l’ajout d’une review pour un jeu vidéo (parcours utilisateur).
     * Vérifie que le flux complet fonctionne :
     * formulaire visible → soumission → 302 → review en base → formulaire disparu après redirection.
     */
    public function testShouldAddReview(): void
    {
        // utilisateur connecté
        // user+1 n'a pas encore noté jeu-video-0 (seul user+0 l'a noté en fixture)
        $this->login('user+1@email.com');

        // 1. Accès à la page du jeu. Le formulaire est visible pour un utilisateur autorisé
        $this->get('/jeu-video-0');
        self::assertResponseIsSuccessful();
        // #pane-reviews → <div id="pane-reviews"> dans Tabs.html.twig (onglet "Avis")
        // form → <form> généré par form_start(form) dans show.html.twig, affiché uniquement si is_granted('review', video_game)
        self::assertSelectorExists('#pane-reviews form');

        // 2. Soumission avec des données valides → redirection 302
        $this->client->submitForm('Poster', [
            'review[rating]' => 3,
            'review[comment]' => 'Super jeu !',
        ]);
        // vérifie 2 assertions :
        // Le code HTTP est un code de redirection (301, 302, 303, 307 ou 308)
        // L'URL dans le header Location vaut /jeu-video-0
        self::assertResponseRedirects('/jeu-video-0'); // compte pour 2 assertions

        // 3. La review est enregistrée en base avec les bonnes données
        $this->getEntityManager()->clear(); // vide le cache de l'EntityManager pour forcer la requête en base et éviter les données en cache
        // Récupère la review en base pour vérifier qu'elle a été enregistrée correctement
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user+1@email.com']);
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-0']);
        $review = $this->getEntityManager()->getRepository(Review::class)->findOneBy([
            'user' => $user,
            'videoGame' => $videoGame,
        ]);
        self::assertNotNull($review); // vérifie que la review existe en base
        self::assertSame(3, $review->getRating()); // vérifie que la note est bien enregistrée
        self::assertSame('Super jeu !', $review->getComment()); // vérifie que le commentaire est bien enregistré

        // 4. Le formulaire n'est plus affiché (voter refuse un second avis)
        $this->client->followRedirect();
        // Le voter VideoGameVoter refuse un second avis → is_granted() retourne false → form absent du HTML
        // #pane-reviews → <div id="pane-reviews"> dans Tabs.html.twig (onglet "Avis")
        // form → <form> généré par form_start(form) dans show.html.twig, conditionné par is_granted('review', video_game)
        self::assertSelectorNotExists('#pane-reviews form');
    }

    /**
     * Vérifie qu'une soumission de données invalides renvoie 422
     * et que le formulaire est réaffiché.
     *
     * Cas testés (dataProvider) :
     *  - note manquante
     *  - commentaire trop long
     *
     * Étapes :
     *  1. user+1 accède à la page du jeu → statut 200, formulaire visible.
     *  2. On remplit le formulaire avec des valeurs invalides.
     *     disableValidation() permet d'envoyer des données invalides
     *     malgré la validation HTML du DomCrawler.
     *  3. Soumission du formulaire → Symfony renvoie 422
     *     car les données ne passent pas la validation du FormType.
     *  4. Le formulaire doit être réaffiché avec les erreurs.
     */

    /**
     * @dataProvider provideInvalidFormData
     *
     * @param array<string, string> $values
     */
    public function testShouldReturn422WhenFormDataIsInvalid(array $values): void
    {
        // user+1 n'a pas encore noté jeu-video-0
        $this->login('user+1@email.com');

        $this->get('/jeu-video-0');
        // dump($this->client->getResponse()->getStatusCode());// 200 page jeu chargée
        self::assertResponseIsSuccessful();

        // disableValidation() est nécessaire pour bypasser la validation HTML du DomCrawler
        // (ChoiceFormField rejette les valeurs hors choix avant même d'envoyer la requête)
        $form = $this->client->getCrawler()->selectButton('Poster')->form();
        $form->disableValidation();
        $form->setValues($values);
        $this->client->submit($form);

        // dump($this->client->getResponse()->getStatusCode());//statut 422
        // dump($this->client->getResponse()->getContent());//formulaire avec erreurs
        // Le formulaire est invalide → le contrôleur re-rend la vue avec un statut 422
        self::assertResponseStatusCodeSame(422);
        // Le formulaire est toujours affiché avec les erreurs
        self::assertSelectorExists('#pane-reviews form');
    }

    /**
     * Fournit les jeux de données invalides pour testShouldReturn422WhenFormDataIsInvalid.
     *
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideInvalidFormData(): iterable
    {
        yield 'note manquante' => [['review[rating]' => '',  'review[comment]' => 'Super jeu !']];
        yield 'commentaire trop long' => [['review[rating]' => '3', 'review[comment]' => str_repeat('a', 1001)]];
    }

    /**
     * Vérifie que le formulaire d'ajout de review n'est pas affiché
     * pour un utilisateur non authentifié.
     *
     * Le VideoGameVoter retourne false si l'utilisateur n'est pas une instance de User
     * → is_granted('review', video_game) vaut false → le formulaire est absent du HTML.
     */
    public function testShouldNotShowFormWhenUserIsNotAuthenticated(): void
    {
        // Aucun login : l'utilisateur est anonyme
        $this->get('/jeu-video-0');
        // dump($this->client->getResponse()->getStatusCode()); // 200 page jeu chargée publique
        // dump($this->client->getResponse()->getContent()); // html généré sans formulaire
        self::assertResponseIsSuccessful();

        // Le voter refuse l'accès → le formulaire ne doit pas être affiché
        // #pane-reviews → <div id="pane-reviews"> dans Tabs.html.twig (onglet "Avis")
        // form → conditionné par is_granted('review', video_game) dans show.html.twig
        self::assertSelectorNotExists('#pane-reviews form');
    }

    /**
     * Vérifie qu'un utilisateur non connecté qui envoie un POST valide
     * est redirigé vers la page de login (302 → /auth/login).
     *
     * Note : avec un firewall form_login + lazy:true, Symfony redirige vers le login
     * plutôt que de renvoyer 401 (comportement réservé aux API stateless).
     * denyAccessUnlessGranted() dans le contrôleur déclenche cette redirection.
     */
    public function testShouldRedirectToLoginWhenUnauthenticatedUserPostsReview(): void
    {
        // Aucun login : l'utilisateur est anonyme
        $this->client->request('POST', '/jeu-video-0', [
            'review' => [
                'rating' => 3,
                'comment' => 'Super jeu !',
            ],
        ]);

        // dump($this->client->getResponse()->getStatusCode()); // 302 redirection
        // dump($this->client->getResponse()->headers->all());  // Location: /auth/login

        // Le firewall form_login redirige l'anonyme vers la page de login
        self::assertResponseRedirects('/auth/login');
    }
}
