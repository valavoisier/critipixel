<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    // setup() est appelé avant chaque test pour initialiser le client HTTP utilisé pour faire des requêtes dans les tests fonctionnels.
    protected function setUp(): void
    {
        parent::setUp();
        // Création d'un client HTTP pour faire des requêtes dans les tests fonctionnels
        $this->client = static::createClient();
    }

    // getEntityManager() est une méthode utilitaire pour accéder à l'EntityManager de Doctrine, qui permet d'interagir avec la base de données dans les tests fonctionnels.
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->service(EntityManagerInterface::class);
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    // service() est une méthode générique pour récupérer n'importe quel service du conteneur de dépendances de Symfony, ce qui facilite l'accès aux services nécessaires dans les tests fonctionnels.
        protected function service(string $id): object
    {
        return $this->client->getContainer()->get($id);
    }

    // get() est une méthode utilitaire pour faire des requêtes GET dans les tests fonctionnels, simplifiant ainsi l'accès aux différentes pages de l'application.
    protected function get(string $uri, array $parameters = []): Crawler
    {
        return $this->client->request('GET', $uri, $parameters);
    }

    // login() est une méthode utilitaire pour simuler la connexion d'un utilisateur dans les tests fonctionnels, ce qui est essentiel pour tester les fonctionnalités qui nécessitent une authentification.
    protected function login(string $email = 'user+0@email.com'): void
    {
        $user = $this->service(EntityManagerInterface::class)->getRepository(User::class)->findOneByEmail($email);

        $this->client->loginUser($user);
    }
}
