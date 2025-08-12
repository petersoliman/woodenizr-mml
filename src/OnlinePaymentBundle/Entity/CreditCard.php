<?php

namespace App\OnlinePaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\UserBundle\Entity\User;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="credit_card")
 * @ORM\Entity(repositoryClass="App\OnlinePaymentBundle\Repository\CreditCardRepository")
 */
class CreditCard
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     */
    private ?User $user = null;

    /**
     * @ORM\OneToOne(targetEntity="App\OnlinePaymentBundle\Entity\Payment")
     */
    private ?Payment $payment = null;

    /**
     * @ORM\Column(name="token", type="text")
     */
    private ?string $token = null;

    /**
     * @ORM\Column(name="masked_pan", type="string", length=45)
     */
    private ?string $maskedPan = null;

    /**
     * Ex. MasterCard or Visa
     * @ORM\Column(name="card_type", type="string", length=45)
     */
    private ?string $cardType = null;

    /**
     * @ORM\Column(name="valid", type="boolean")
     */
    private ?bool $valid = false;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $created=null;

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     */
    public function updatedTimestamps()
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getMaskedPan(): ?string
    {
        return $this->maskedPan;
    }

    public function setMaskedPan(string $maskedPan): self
    {
        $this->maskedPan = $maskedPan;

        return $this;
    }

    public function getCardType(): ?string
    {
        return $this->cardType;
    }

    public function setCardType(string $cardType): self
    {
        $this->cardType = $cardType;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function getValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }
}
