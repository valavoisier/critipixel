<?php

declare(strict_types=1);

namespace App\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;

/**
 * Service chargé de calculer :
 * - la moyenne des notes d’un jeu vidéo
 * - le nombre de notes par valeur (1 à 5)
 *
 * Il implémente deux interfaces :
 * - CalculateAverageRating
 * - CountRatingsPerValue
 *
 * La classe est readonly : ses dépendances ne peuvent pas changer après construction.
 */
final readonly class RatingHandler implements CalculateAverageRating, CountRatingsPerValue
{
    /**
     * Calcule la moyenne des notes d’un jeu vidéo.
     *
     * - Si le jeu n’a aucune review → moyenne = null
     * - Sinon : somme des notes / nombre de reviews, arrondi à l’entier supérieur
     */
    public function calculateAverage(VideoGame $videoGame): void
    {
        // Aucun avis → pas de moyenne
        if (0 === count($videoGame->getReviews())) {
            $videoGame->setAverageRating(null);

            return;
        }

        // Somme des notes (extraction des ratings via array_map)
        $ratingsSum = array_sum(
            array_map(
                static fn (Review $review): int => $review->getRating(),
                $videoGame->getReviews()->toArray()
            )
        );

        // Calcul de la moyenne arrondie au supérieur
        $videoGame->setAverageRating((int) ceil($ratingsSum / count($videoGame->getReviews())));
    }

    /**
     * Compte combien de notes de chaque valeur (1 à 5) un jeu possède.
     *
     * Exemple :
     * - 3 notes de 5
     * - 1 note de 3
     * - 0 note de 1
     *
     * Le résultat est stocké dans l’objet NumberOfRatingsPerValue du VideoGame.
     */
    public function countRatingsPerValue(VideoGame $videoGame): void
    {
        // On réinitialise le compteur avant de recalculer
        $videoGame->getNumberOfRatingsPerValue()->clear();

        // Aucun avis → rien à compter
        if (0 === count($videoGame->getReviews())) {
            return;
        }

        // Pour chaque review, on incrémente le compteur correspondant à la note
        foreach ($videoGame->getReviews() as $review) {
            match ($review->getRating()) {
                1 => $videoGame->getNumberOfRatingsPerValue()->increaseOne(),
                2 => $videoGame->getNumberOfRatingsPerValue()->increaseTwo(),
                3 => $videoGame->getNumberOfRatingsPerValue()->increaseThree(),
                4 => $videoGame->getNumberOfRatingsPerValue()->increaseFour(),
                default => $videoGame->getNumberOfRatingsPerValue()->increaseFive(),
            };
        }
    }
}
