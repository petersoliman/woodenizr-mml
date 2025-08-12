<?php

namespace App\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("product_collection")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductHasCollectionRepository")
 */
class ProductHasCollection
{

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Collection", inversedBy="productHasCollections")
     */
    private ?Collection $collection = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="productHasCollections")
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
    public function updatedTimestamps()
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }


    public function __clone()
    {
        if ($this->product) {
            $this->product = null;
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

    public function getCollection(): ?Collection
    {
        return $this->collection;
    }

    public function setCollection(?Collection $collection): self
    {
        $this->collection = $collection;

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
