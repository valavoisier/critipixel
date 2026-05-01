<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Tag;
use App\Tests\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{
    /**
     * Vérifie l’affichage initial de la page d’accueil :
     * - réponse HTTP 200
     * - 10 jeux vidéo affichés (pagination par défaut)
     * - navigation vers la page 2 fonctionnelle
     */
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }

    /**
     * Vérifie le filtrage par recherche textuelle :
     * - affichage initial : 10 jeux
     * - recherche "Jeu vidéo 49" → exactement 1 résultat
     */
    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    /**
     * Vérifie le filtrage des jeux vidéo par tags.
     *
     * * Les tags sont résolus dynamiquement en base via leur nom,
     * ce qui rend les tests indépendants des IDs générés par Doctrine.
     *
     * Cas fournis par le data provider :
     * - aucun tag
     * - un tag
     * - plusieurs tags
     * 
     * @dataProvider provideTagFilterCases
     * @param string[] $tagNames   Noms des tags à sélectionner dans le filtre
     * @param int      $expectedCount Nombre de cartes attendues sur la première page
     */
    public function testShouldFilterVideoGamesByTags(array $tagNames, int $expectedCount): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();

        // Conversion des noms de tags en IDs utilisés par le formulaire
        $values = [];
        if (!empty($tagNames)) {
            $values['filter[tags]'] = array_map(
                fn(string $name): int => $this->getEntityManager()
                    ->getRepository(Tag::class)
                    ->findOneBy(['name' => $name])
                    ->getId(),
                $tagNames
            );
        }

        $this->client->submitForm('Filtrer', $values, 'GET');// valeurs envoyées dans l'URL
// dump($this->client->getRequest()->query->all()); // paramètres GET envoyés
        self::assertResponseIsSuccessful();
// dump($this->client->getResponse()->getContent()); // HTML de la page filtrée
        // vérifie que le nombre de cartes affichées correspond au résultat attendu pour chaque cas de filtrage
        self::assertSelectorCount($expectedCount, 'article.game-card');
    }

    /**
     * Cas de filtrage par tags basés sur la logique des fixtures :
     * - 50 jeux, 8 tags
     * - chaque jeu possède 2 tags consécutifs
     *
     * Résultats attendus :
     * - aucun tag → 10 jeux (pagination)
     * - "Action" → 10 jeux (13 au total)
     * - "Action" + "RPG" → 7 jeux
     
     * @return iterable<string, array{tagNames: string[], expectedCount: int}>
     */
    public static function provideTagFilterCases(): iterable
    {
        yield 'aucun tag'           => [[], 10];
        yield 'tag Action seul'     => [['Action'], 10]; // 13 jeux, 10 par page
        yield 'tags Action et RPG'  => [['Action', 'RPG'], 7]; // 7 jeux, tous sur la 1ère page
    }

    /**
     * Un tag inexistant doit retourner 0 résultat.
     */
    public function testShouldReturnNoResultsForNonExistentTag(): void
    {
        $this->get('/', ['filter' => ['tags' => [99999]]]);
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, 'article.game-card');
    }

}

