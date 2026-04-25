<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Tests\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{
    //vérifier que l'on peut accéder à la page d'accueil, puis vérifier que les jeux vidéo sont affichés par page de 10, que la pagination fonctionne et que le filtre de recherche affiche les résultats corrects
    //GET / → 200, 10 jeux vidéo affichés, pagination fonctionne, recherche "Jeu vidéo 49" affiche 1 résultat
    //GET / → 200, 10 cartes .game-card, pagination page 2 fonctionne
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }
    //GET / → 200, 10 cartes .game-card, pagination page 2 fonctionne
    //Filtre "Jeu vidéo 49" → exactement 1 résultat
    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }
}
