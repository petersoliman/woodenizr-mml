<?php

namespace App\OnlinePaymentBundle\Entity;

use App\MediaBundle\Entity\Image;
use App\OnlinePaymentBundle\Entity\Translation\PaymentMethodTranslation;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("payment_method")
 * @UniqueEntity("type")
 * @ORM\Entity(repositoryClass="App\OnlinePaymentBundle\Repository\PaymentMethodRepository")
 */
class PaymentMethod implements Translatable, DateTimeInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait;

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
     * @ORM\OneToOne(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     */
    private ?Image $image = null;

    /**
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private ?string $note = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="type", type="string", enumType=PaymentMethodEnum::class, length=255, unique=true, nullable=false)
     */
    private ?PaymentMethodEnum $type = null;

    /**
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private bool $active = false;

    /**
     * @ORM\Column(name="fees", type="float", nullable=true)
     */
    private float $fees = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\OnlinePaymentBundle\Entity\Translation\PaymentMethodTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;


    private ?float $currentFee = null;


    public function getName(): ?string
    {
        return $this->getTitle();
    }

    public function setName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function getCurrentFees(): ?float
    {
        if ($this->currentFee === null) {
            $this->setCurrentFees($this->getFees());
        }

        return $this->currentFee;
    }

    public function setCurrentFees($currentFee): PaymentMethod
    {
        $this->currentFee = $currentFee;

        return $this;
    }

    public function getTypeName(): ?string
    {
        if ($this->getType() instanceof PaymentMethodEnum) {
            return $this->getType()->name();
        }

        return null;
    }

    public function getType(): ?PaymentMethodEnum
    {
        return $this->type;
    }

    public function getObj(): array
    {
        return [
            "id" => (int)$this->getId(),
            "name" => (string)$this->getTitle(),
            "type" => (string)$this->getType()->name,
            "fees" => (float)$this->getCurrentFees(),
        ];
    }

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    function __toString()
    {
        return $this->getTitle();
    }

    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }


    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getFees(): ?float
    {
        return $this->fees;
    }

    public function setFees(?float $fees): self
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * @return Collection<int, PaymentMethodTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(PaymentMethodTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(PaymentMethodTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }

}
