<?php

declare(strict_types=1);

namespace App\Rating;

use App\Model\Entity\VideoGame;

/**
 * Contrat pour tout service capable de compter
 * combien de notes de chaque valeur (1 à 5)
 * un jeu vidéo possède.
 *
 * Une classe qui implémente cette interface doit fournir
 * une méthode countRatingsPerValue() qui met à jour
 * l'objet VideoGame avec les compteurs correspondants.
 */
interface CountRatingsPerValue
{
    /**
     * Compte les notes par valeur et met à jour le jeu.
     */
    public function countRatingsPerValue(VideoGame $videoGame): void;
}
