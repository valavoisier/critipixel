<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // findAll() retourne array sans type précis — on indique à l'IDE que les éléments sont des Tag
        /** @var Tag[] $tags */
        $tags = $manager->getRepository(Tag::class)->findAll();

        // array_fill_callback() retourne array sans type précis — on indique à l'IDE que les éléments sont des VideoGame
        /** @var VideoGame[] $videoGames */
        $videoGames = array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );

        // Associer 2 tags à chaque jeu (cycle sur les 8 genres) avant la persistance
        foreach ($videoGames as $index => $videoGame) {
            $videoGame->getTags()->add($tags[$index % count($tags)]);
            $videoGame->getTags()->add($tags[($index + 1) % count($tags)]);
        }

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();

        // Ajouter des reviews aux jeux : note obligatoire (1-5), commentaire optionnel (1 review sur 2)
        // findAll() retourne array sans type précis — on indique à l'IDE que les éléments sont des User
        /** @var User[] $users */
        $users = $manager->getRepository(User::class)->findAll();
        foreach ($videoGames as $index => $videoGame) {
            // Nombre de reviews variable selon le jeu (1 à 3)
            $numberOfReviews = ($index % 3) + 1;
            for ($i = 0; $i < $numberOfReviews; $i++) {
                $review = (new Review())
                    ->setVideoGame($videoGame)
                    ->setUser($users[($index + $i) % count($users)])
                    // Note obligatoire : cycle de 1 à 5
                    ->setRating((($index + $i) % 5) + 1)
                    // Commentaire optionnel : présent 1 review sur 2
                    ->setComment($i % 2 === 0 ? $this->faker->sentence(10) : null);
                $manager->persist($review);
            }
        }

        // Flush d'abord pour que les reviews soient en base
        $manager->flush();

        // Recalcule la moyenne et le décompte par valeur après le flush
        // (getReviews() charge la collection depuis la base, pas depuis la mémoire)
        foreach ($videoGames as $videoGame) {
            $manager->refresh($videoGame);
            $this->calculateAverageRating->calculateAverage($videoGame);
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);
        }

        $manager->flush();

    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, TagFixtures::class];
    }
}
