<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Vous devez vérifier votre adresse e-mail avant de vous connecter.'
            );
        }

        if ($user->isSuspended()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est suspendu.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
