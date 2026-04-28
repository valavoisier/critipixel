<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

class RatingHandlerTest extends TestCase
{
    /**
     * @dataProvider provideRatingsForAverage
     */
    //vérifier que la méthode calculateAverage() calcule correctement la moyenne des notes d’un jeu vidéo, en testant différents scénarios : aucun avis, un seul avis, plusieurs avis avec une moyenne arrondie (4 appels phpunit)
    public function testCalculateAverage(array $ratings, ?int $expectedAverage): void
    {
        // création des objets directement sans BDD
        $ratingHandler = new RatingHandler();
        $videoGame = new VideoGame();

        // Ajout des reviews au jeu vidéo
        foreach ($ratings as $rating) {
            $review = (new Review())->setRating($rating);
            $videoGame->getReviews()->add($review);
        }

        // Calcul de la moyenne
        $ratingHandler->calculateAverage($videoGame);

        // Vérification que la moyenne calculée correspond à la moyenne attendue
        // vérification type et valeur de la moyenne (int ou null)
        self::assertSame($expectedAverage, $videoGame->getAverageRating());
    }

    //Data Provider pour testCalculateAverage() : différentes combinaisons de notes et la moyenne attendue, 
    //iterable pour pouvoir yield plusieurs scénarios de test
    public static function provideRatingsForAverage(): iterable 
    {
        yield 'aucune note'      => [[], null];
        yield 'une seule note'   => [[3], 3];
        yield 'plusieurs notes'  => [[1, 2, 3, 4, 5], 3];   // ceil(15/5) = 3
        yield 'moyenne arrondie' => [[1, 2], 2];             // ceil(3/2) = ceil(1.5) = 2
    }

    public function testCountRatingsPerValue(): void
    {
        // TODO : écrire les tests pour countRatingsPerValue()
    }
}
