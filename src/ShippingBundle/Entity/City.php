<?php

namespace App\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("city")
 * @ORM\Entity(repositoryClass="App\ShippingBundle\Repository\CityRepository")
 */
class City implements DateTimeInterface
{

    use DateTimeTrait,
        VirtualDeleteTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=45)
     */
    private ?string $title = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="price", type="float", nullable=false)
     */
    private ?float $price = null;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;


    public function getObj(): array
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
        ];
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }
}
