<?php

namespace App\UserBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

interface UserInterface extends BaseUserInterface
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * Returns the user unique id.
     *
     * @return mixed
     */
    public function getId();


    /**
     * Gets the canonical email in search and sort queries.
     *
     * @return string
     */
    public function getUserIdentifier(): ?string;

    /**
     * Gets email.
     *
     * @return string
     */
    public function getEmail(): ?string;

    /**
     * Sets the email.
     *
     * @param string $email
     *
     * @return static
     */
    public function setEmail($email);

    /**
     * Gets the canonical email in search and sort queries.
     *
     * @return string
     */
    public function getEmailCanonical(): ?string;

    /**
     * Sets the canonical email.
     *
     * @param string $emailCanonical
     *
     * @return static
     */
    public function setEmailCanonical(string $emailCanonical);

    /**
     * Sets the hashed password.
     *
     * @param string $password
     *
     * @return static
     */
    public function setPassword(string $password);

    /**
     * Tells if the the given user has the super admin role.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool;

    /**
     * @param bool $boolean
     *
     * @return static
     */
    public function setEnabled(bool $boolean);

    /**
     * Sets the super admin status.
     *
     * @param bool $boolean
     *
     * @return static
     */
    public function setSuperAdmin(bool $boolean);

    /**
     * Gets the confirmation token.
     *
     * @return string|null
     */
    public function getConfirmationToken(): ?string;

    /**
     * Sets the confirmation token.
     *
     * @param string|null $confirmationToken
     *
     * @return static
     */
    public function setConfirmationToken(?string $confirmationToken);

    /**
     * Sets the timestamp that the user requested a password reset.
     *
     * @param null|\DateTime $date
     *
     * @return static
     */
    public function setPasswordRequestedAt(\DateTime $date = null);

    /**
     * Sets the last login time.
     *
     * @param \DateTime|null $time
     *
     * @return static
     */
    public function setLastLogin(\DateTime $time = null);

    /**
     * Never use this to check if this user has access to anything!
     *
     * Use the AuthorizationChecker, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $authorizationChecker->isGranted('ROLE_USER');
     *
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool;

    /**
     * Sets the roles of the user.
     *
     * This overwrites any previous roles.
     *
     * @param array $roles
     *
     * @return static
     */
    public function setRoles(array $roles);

    /**
     * Adds a role to the user.
     *
     * @param string $role
     *
     * @return static
     */
    public function addRole(string $role);

    /**
     * Removes a role to the user.
     *
     * @param string $role
     *
     * @return static
     */
    public function removeRole(string $role);
}