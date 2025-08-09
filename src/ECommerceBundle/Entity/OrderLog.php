<?php

namespace App\ECommerceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("order_log")
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\OrderLogRepository")
 */
class OrderLog
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="logs")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Order $order;

    /**
     * @Assert\NotNull()
     * @ORM\Column(name="text", type="text")
     */
    private ?string $text = null;

    /**
     * @ORM\Column(name="creator", type="string", length=30)
     */
    private ?string $creator = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $created = null;

    /**
     * @ORM\PrePersist
     */
    public function updatedTimestamps(): void
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }
}
