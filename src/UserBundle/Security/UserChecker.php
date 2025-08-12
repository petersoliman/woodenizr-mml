<?php

namespace App\UserBundle\Security;

use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getDeleted() instanceof \DateTimeInterface) {
            throw new CustomUserMessageAccountStatusException("Your user account no longer exists.");
        }
        if (!$user->getEnabled()) {
            throw new CustomUserMessageAccountStatusException("Your account is not active");
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setLastLogin(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();
    }

}