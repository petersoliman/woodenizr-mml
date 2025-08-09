<?php

namespace App\UserBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

abstract class BaseUser implements UserInterface, PasswordAuthenticatedUserInterface, DateTimeInterface
{
    use DateTimeTrait;

    /**
     * @ORM\Column(name="email", type="string", length=180, unique=true)
     * @Assert\NotBlank
     * @Assert\Email(message="The email '{{ value }}' is not a valid email")
     * @Assert\Length(
     *     min="2",
     *     max="120",
     *     minMessage="Your email must be at least {{ limit }} characters long",
     *     maxMessage="Your email cannot be longer than {{ limit }} characters",
     * )
     */
    protected ?string $email = null;

    /**
     * @ORM\Column(name="email_canonical", type="string", length=180, unique=true)
     */
    protected ?string $emailCanonical;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var ?string
     */
    protected ?string $plainPassword = null;

    /**
     * @var ?string The hashed password
     * @ORM\Column(name="password", type="string", length=255)
     */
    protected ?string $password = null;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected bool $enabled = true;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected ?\DateTimeInterface $lastLogin;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected ?\DateTimeInterface $passwordRequestedAt;

    /**
     * @ORM\Column(name="roles", type="json")
     */
    protected array $roles = [];

    /**
     * @ORM\Column(name="confirmation_token", type="string", nullable=true)
     */
    protected ?string $confirmationToken;

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_DEFAULT;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): BaseUser
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): BaseUser
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function setSuperAdmin(bool $boolean): BaseUser
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword): BaseUser
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): BaseUser
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail($email): BaseUser
    {
        $this->email = $email;

        return $this;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical(string $emailCanonical): UserInterface
    {
        $this->emailCanonical = strtolower($this->email);

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->getEnabled();
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): UserInterface
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTime $lastLogin = null): UserInterface
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(\DateTime $passwordRequestedAt = null)
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): UserInterface
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }
}