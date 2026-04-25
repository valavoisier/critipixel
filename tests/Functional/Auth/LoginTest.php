<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class LoginTest extends FunctionalTestCase
{
    //vérifier que l'on peut se connecter avec les bonnes informations d'identification-> IS_AUTHENTICATED = true, puis logout révoque l'accès
    public function testThatLoginShouldSucceeded(): void
    {
        $this->get('/auth/login');

        $this->client->submitForm('Se connecter', [
            'email' => 'user+1@email.com',
            'password' => 'password'
        ]);

        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);

        self::assertTrue($authorizationChecker->isGranted('IS_AUTHENTICATED'));

        $this->get('/auth/logout');

        self::assertFalse($authorizationChecker->isGranted('IS_AUTHENTICATED'));
    }
    //vérifier que l'on ne peut pas se connecter avec les mauvaises informations d'identification / Mauvais mot de passe-> IS_AUTHENTICATED = false
    public function testThatLoginShouldFailed(): void
    {
        $this->get('/auth/login');

        $this->client->submitForm('Se connecter', [
            'email' => 'user+1@email.com',
            'password' => 'fail'
        ]);

        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);

        self::assertFalse($authorizationChecker->isGranted('IS_AUTHENTICATED'));
    }
}
