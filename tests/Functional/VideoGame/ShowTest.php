<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ShowTest extends FunctionalTestCase
{
    //vérifier que l'on peut accéder à la page de détail d'un jeu vidéo avec une URL valide, puis vérifier que les informations du jeu vidéo sont affichées correctement
    //GET /jeu-video-0 → 200, <h1> contient "Jeu vidéo 0"
    public function testShouldShowVideoGame(): void
    {
        $this->get('/jeu-video-0');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Jeu vidéo 0');
    }
}
