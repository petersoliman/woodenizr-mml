<?php

namespace App\ProductBundle\Entity;

use App\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("product_favorite")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductFavoriteRepository")
 */
class ProductFavorite
{

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\UserBundle\Entity\User")
     */
    private ?User $user = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Product", inversedBy="favorites")
     */
    private ?Product $product = null;


    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $created = null;

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }


}
