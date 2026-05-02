<?php

namespace App\Security\Voter;

use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Ce voter est utilisé pour vérifier si un utilisateur peut laisser une review sur un jeu vidéo.
// Il est utilisé dans le template show.html.twig pour conditionner l'affichage du formulaire de review (is_granted('review', video_game)).
// utilisateur non connecté ou ayant déjà voté (false) → formulaire de review non affiché
// utilisateur connecté et n'ayant pas encore voté (true) → formulaire de review affiché

/**
 * @extends Voter<string, VideoGame>
 */
class VideoGameVoter extends Voter
{
    public const REVIEW = 'review';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::REVIEW && $subject instanceof VideoGame;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return !$subject->hasAlreadyReview($user);
    }
}
