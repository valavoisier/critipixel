<?php

declare(strict_types=1);

namespace App\Rating;

use App\Model\Entity\VideoGame;

/**
 * Contrat pour tout service capable de calculer
 * la moyenne des notes d’un jeu vidéo.
 *
 * Une classe qui implémente cette interface doit fournir
 * une méthode calculateAverage() qui modifie l'objet VideoGame
 * en lui attribuant une moyenne (ou null s'il n'y a pas de notes).
 */
interface CalculateAverageRating
{
    /**
     * Calcule et met à jour la moyenne des notes du jeu.
     */
    public function calculateAverage(VideoGame $videoGame): void;
}
