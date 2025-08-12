<?php

namespace App\UserBundle\Security;

use App\UserBundle\Entity\User;
use App\UserBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;


class UserProvider implements UserProviderInterface
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function refreshUser(UserInterface $user):UserInterface
    {
        if (!$user instanceof User) {
            throw new UserNotFoundException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }
        if (!$user->getEnabled()) {
            throw new UserNotFoundException('Your account is not active.');
        }

        if ($user->getDeleted() instanceof \DateTimeInterface) {
            throw new UserNotFoundException('Your user account no longer exists.');
        }
        $email = $user->getUserIdentifier();

        return $this->fetchUser($email);
    }

    public function supportsClass(string $class): bool
    {
        $userClass = $this->userRepository->getClassName();

        return $userClass === $class || is_subclass_of($class, $userClass);
    }

    public function loadUserByIdentifier(string $usernameIdentifier): UserInterface
    {
        return $this->fetchUser($usernameIdentifier);
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    private function fetchUser($email): ?User
    {
        $user = $this->userRepository->findOneBy(["email" => $email]);
        if (!$user) {
            throw new UserNotFoundException(sprintf('Email "%s" does not exist.', $email));
        }

        return $user;
    }

}