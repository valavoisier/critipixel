<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Model\Entity\User;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterTest extends FunctionalTestCase
{
    //vérifier que l'on peut s'inscrire avec des données valides, puis vérifier que l'utilisateur a été créé en base de données avec les bonnes données et que le mot de passe est correctement hashé
    //Inscription valide → redirect /auth/login, utilisateur en BDD, password hashé valide
    public function testThatRegistrationShouldSucceeded(): void
    {
        $this->get('/auth/register');

        $this->client->submitForm('S\'inscrire', self::getFormData());

        self::assertResponseRedirects('/auth/login');

        $user = $this->getEntityManager()->getRepository(User::class)->findOneByEmail('user@email.com');

        $userPasswordHasher = $this->service(UserPasswordHasherInterface::class);

        self::assertNotNull($user);
        self::assertSame('username', $user->getUsername());
        self::assertSame('user@email.com', $user->getEmail());
        self::assertTrue($userPasswordHasher->isPasswordValid($user, 'SuperPassword123!'));
    }

    /**
     * @dataProvider provideInvalidFormData
     */
    //vérifier que l'on ne peut pas s'inscrire avec des données invalides, puis vérifier que les erreurs de validation sont affichées et que l'utilisateur n'est pas créé en base de données
    //Retour 422 pour : username vide/non-unique/trop long, email vide/non-unique/invalide
    public function testThatRegistrationShouldFailed(array $formData): void
    {
        $this->get('/auth/register');

        $this->client->submitForm('S\'inscrire', $formData);

        self::assertResponseIsUnprocessable();
    }

    public static function provideInvalidFormData(): iterable
    {
        yield 'empty username' => [self::getFormData(['register[username]' => ''])];
        yield 'non unique username' => [self::getFormData(['register[username]' => 'user+1'])];
        yield 'too long username' => [self::getFormData(['register[username]' => 'Lorem ipsum dolor sit amet orci aliquam'])];
        yield 'empty email' => [self::getFormData(['register[email]' => ''])];
        yield 'non unique email' => [self::getFormData(['register[email]' => 'user+1@email.com'])];
        yield 'invalid email' => [self::getFormData(['register[email]' => 'fail'])];
    }

    public static function getFormData(array $overrideData = []): array
    {
        return $overrideData + [
            'register[username]' => 'username',
            'register[email]' => 'user@email.com',
            'register[plainPassword]' => 'SuperPassword123!'
        ];
    }
}
