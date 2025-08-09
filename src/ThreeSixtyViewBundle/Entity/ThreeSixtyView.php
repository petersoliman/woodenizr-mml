<?php

namespace App\ThreeSixtyViewBundle\Entity;

use App\MediaBundle\Entity\Image;
use App\ThreeSixtyViewBundle\Enums\ThreeSixtyViewImageExtensionEnums;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="360_view")
 * @ORM\Entity(repositoryClass="App\ThreeSixtyViewBundle\Repository\ThreeSixtyViewRepository")
 */
class ThreeSixtyView implements DateTimeInterface
{

    use DateTimeTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="image_extension", enumType=ThreeSixtyViewImageExtensionEnums::class, type="string", length=255)
     */
    private ?ThreeSixtyViewImageExtensionEnums $imageExtension = ThreeSixtyViewImageExtensionEnums::IMAGE_EXTENSION_PNG;

    /**
     * @ORM\ManyToMany(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     * @ORM\OrderBy({"tarteb" = "ASC"})
     */
    private Collection $images;


    public function hasImage(Image $image): bool
    {
        if (!$this->images->contains($image)) {
            return false;
        }

        return true;
    }

    public function getImageExtensionName(): ?string
    {
        if ($this->getImageExtension() instanceof ThreeSixtyViewImageExtensionEnums) {
            return $this->getImageExtension()->name();
        }

        return null;
    }

    public function getImageExtension(): ?ThreeSixtyViewImageExtensionEnums
    {
        return $this->imageExtension;
    }

    public function setImageExtension(ThreeSixtyViewImageExtensionEnums $imageExtension): self
    {
        $this->imageExtension = $imageExtension;

        return $this;
    }

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        $this->images->removeElement($image);

        return $this;
    }
}
