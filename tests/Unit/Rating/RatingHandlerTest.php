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
    //vérifier que la méthode calculateAverage() calcule correctement la moyenne des notes d’un jeu vidéo, 
    //en testant différents scénarios : aucun avis, un seul avis, plusieurs avis avec une moyenne arrondie (4 appels phpunit)
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
        yield 'aucune note'      => [[], null]; // pas de notes → moyenne = null
        yield 'une seule note'   => [[3], 3]; // une seule note → moyenne = cette note
        yield 'plusieurs notes'  => [[1, 2, 3, 4, 5], 3];   // ceil(15/5) = 3
        yield 'moyenne arrondie' => [[1, 2], 2];             // ceil(3/2) = ceil(1.5) = 2
    }

    /**
     * @dataProvider provideRatingsForCount
     */
    // vérifier que countRatingsPerValue() remplit correctement les compteurs par valeur (1 à 5)
    public function testCountRatingsPerValue(array $ratings, int $expectedOne, int $expectedTwo, int $expectedThree, int $expectedFour, int $expectedFive): void
    {
        // création des objets directement sans BDD
        $ratingHandler = new RatingHandler();
        $videoGame = new VideoGame();

        // Ajout des reviews au jeu vidéo
        foreach ($ratings as $rating) {
            //
            $review = (new Review())->setRating($rating);
            $videoGame->getReviews()->add($review);
        }

        // Comptage des notes par valeur - appel de la méthode à tester
        // La méthode parcourt les reviews et incrémente les compteurs dans NumberOfRatingPerValue.
        $ratingHandler->countRatingsPerValue($videoGame);

        // Vérification que les compteurs par valeur correspondent aux compteurs attendus
        //On récupère l'objet NumberOfRatingPerValue et on vérifie chaque compteur individuellement. 
        //On a 5 assertions par appel × 3 scénarios = 15 assertions au total (ok 3 tests, 15 assertions).
        $counts = $videoGame->getNumberOfRatingsPerValue();
        self::assertSame($expectedOne,   $counts->getNumberOfOne());
        self::assertSame($expectedTwo,   $counts->getNumberOfTwo());
        self::assertSame($expectedThree, $counts->getNumberOfThree());
        self::assertSame($expectedFour,  $counts->getNumberOfFour());
        self::assertSame($expectedFive,  $counts->getNumberOfFive());
    }

    // Data Provider pour testCountRatingsPerValue() : notes en entrée + compteurs attendus (one, two, three, four, five),
    // iterable pour pouvoir yield plusieurs scénarios de test
    public static function provideRatingsForCount(): iterable
    {
        yield 'aucune note'    => [[], 0, 0, 0, 0, 0]; // pas de notes → tous les compteurs à 0
        yield 'notes variées'  => [[1, 1, 3, 5], 2, 0, 1, 0, 1]; // 2 notes de 1, 0 note de 2, 1 note de 3, 0 note de 4, 1 note de 5
        yield 'toutes valeurs' => [[1, 2, 3, 4, 5], 1, 1, 1, 1, 1]; // 1 note de chaque valeur de 1 à 5
    }
}
