<?php

declare(strict_types=1);

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class TagFixtures extends Fixture
{
    public const TAGS = [
        'Action',
        'RPG',
        'Aventure',
        'Sport',
        'FPS',
        'Plateforme',
        'Stratégie',
        'Simulation',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::TAGS as $name) {
            $tag = (new Tag())->setName($name);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}
