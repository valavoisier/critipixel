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
            'review[rating]'  => 3,
            'review[comment]' => 'Super jeu !',
        ]);
        //vérifie 2 assertions :
        //Le code HTTP est un code de redirection (301, 302, 303, 307 ou 308)
        //L'URL dans le header Location vaut /jeu-video-0
        self::assertResponseRedirects('/jeu-video-0');//compte pour 2 assertions

        // 3. La review est enregistrée en base avec les bonnes données
        $this->getEntityManager()->clear();// vide le cache de l'EntityManager pour forcer la requête en base et éviter les données en cache
        // Récupère la review en base pour vérifier qu'elle a été enregistrée correctement
        $user      = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user+1@email.com']);
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-0']);
        $review    = $this->getEntityManager()->getRepository(Review::class)->findOneBy([
            'user'      => $user,
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
     * Vérifie qu'une soumission sans note (valeur invalide hors des choix 1-5)
     * renvoie une réponse 422 Unprocessable Entity et réaffiche le formulaire.
     */
    
    /**
    * Test fonctionnel : vérifie la gestion d'une soumission invalide (note manquante).
    *
    * Objectif :
    * - S'assurer que le contrôleur renvoie bien un statut HTTP 422 lorsque la note
    *   envoyée est vide ou hors des valeurs autorisées.
    * - Vérifier que le formulaire est réaffiché afin que l'utilisateur puisse corriger
    *   son erreur.
    *
    * Détails du scénario :
    * 1. L'utilisateur authentifié accède à la page du jeu vidéo.
    * 2. Le formulaire est récupéré puis modifié pour désactiver la validation HTML,
    *    ce qui permet d'envoyer une valeur invalide côté serveur.
    * 3. Le formulaire est soumis avec une note vide.
    * 4. Le contrôleur doit renvoyer un statut 422 (Unprocessable Entity).
    * 5. Le formulaire doit rester visible, indiquant que la validation a échoué.
    */
    public function testShouldReturn422WhenRatingIsMissing(): void
    {
        /* 1. L'utilisateur est connecté*/
        // user+1 n'a pas encore noté jeu-video-0
        $this->login('user+1@email.com');

        /* 2. Accès à la page du jeu → formulaire visible */
        $this->get('/jeu-video-0');
        self::assertResponseIsSuccessful();

        /* 3. Préparation d'une soumission invalide (désactivation validation HTML) 
              Soumission sans note : on désactive la validation côté crawler pour pouvoir
              envoyer une valeur hors des choix valides et tester la validation serveur */
        // Récupère le formulaire du crawler
        $crawler = $this->client->getCrawler();
        // Désactive la validation HTML pour pouvoir soumettre une valeur invalide
        // Le bouton 'Poster' est défini dans show.html.twig, ligne 83      
        $form = $crawler->selectButton('Poster')->form();
        // Modifie les valeurs du formulaire pour envoyer une note vide
        $form->disableValidation();
        // 'review[rating]' correspond au name de l'input de la note dans le formulaire
        // 'review[comment]' correspond au name de l'input du commentaire dans le formulaire
        $form->setValues([
            'review[rating]'  => '',
            'review[comment]' => 'Super jeu !',
        ]);
        
        /* 4. Soumission du formulaire */
        $this->client->submit($form);

        /* 5. Le contrôleur doit renvoyer 422 et réafficher le formulaire */ 
        // Le formulaire est invalide → le contrôleur re-rend la vue avec un statut 422  
        self::assertResponseStatusCodeSame(422);
        // Le formulaire est toujours affiché avec les erreurs
        self::assertSelectorExists('#pane-reviews form');
    }
}
