<?php

namespace App\CMSBundle\Entity;

use App\CMSBundle\Entity\Translation\BannerTranslation;
use App\CMSBundle\Enum\BannerActionButtonPositionEnum;
use App\CMSBundle\Enum\BannerPlacementEnum;
use App\MediaBundle\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;

/**
 * Banner
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="banner")
 * @ORM\Entity(repositoryClass="App\CMSBundle\Repository\BannerRepository")
 */
class Banner implements Translatable, DateTimeInterface
{

    use DateTimeTrait,
        LocaleTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     */
    private ?Image $image = null;

    /**
     * @ORM\Column(name="title", type="string", length=50)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="sub_title", type="string", length=35,nullable=true)
     */
    private ?string $subTitle = null;

    /**
     * @ORM\Column(name="placement", enumType=BannerPlacementEnum::class, type="integer", length=255)
     */
    private ?BannerPlacementEnum $placement = null;

    /**
     * @ORM\Column(name="placementName", type="string", length=255, nullable=true)
     */
    private ?string $placementName = null;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private ?string $url = null;

    /**
     * @ORM\Column(name="text", type="string", length=255, nullable=true)
     */
    private ?string $text = null;

    /**
     * @ORM\Column(name="action_button_name", type="string", length=20, nullable=true)
     */
    private string $actionButtonName = 'View';

    /**
     * @ORM\Column(name="action_button_position", enumType=BannerActionButtonPositionEnum::class, type="string", length=255, nullable=true)
     */
    private ?BannerActionButtonPositionEnum $actionButtonPosition = BannerActionButtonPositionEnum::CENTER;

    /**
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="openInNewTab", type="boolean")
     */
    private bool $openInNewTab = false;

    /**
     * @ORM\OneToMany(targetEntity="App\CMSBundle\Entity\Translation\BannerTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updatePlaceholderName(): void
    {
        $this->setPlacementName($this->getPlacement()->name());
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getTitle()
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function getSubTitle(): ?string
    {
        return !$this->currentTranslation ? $this->subTitle : $this->currentTranslation->getSubTitle();
    }


    public function getUrl(): ?string
    {
        return !$this->currentTranslation ? $this->url : $this->currentTranslation->getUrl();
    }

    public function getText(): ?string
    {
        return !$this->currentTranslation ? $this->text : $this->currentTranslation->getText();
    }

    public function getPlacement(): ?BannerPlacementEnum
    {
        return $this->placement;
    }

    public function setPlacement(BannerPlacementEnum $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

    public function getActionButtonPosition(): ?BannerActionButtonPositionEnum
    {
        return $this->actionButtonPosition;
    }

    public function setActionButtonPosition(?BannerActionButtonPositionEnum $actionButtonPosition): self
    {
        $this->actionButtonPosition = $actionButtonPosition;

        return $this;
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

    public function setSubTitle(?string $subTitle): self
    {
        $this->subTitle = $subTitle;

        return $this;
    }


    public function setPlacementName(?string $placementName): self
    {
        $this->placementName = $placementName;

        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }


    public function getActionButtonName(): ?string
    {
        return $this->actionButtonName;
    }

    public function setActionButtonName(?string $actionButtonName): self
    {
        $this->actionButtonName = $actionButtonName;

        return $this;
    }

    public function getTarteb(): ?int
    {
        return $this->tarteb;
    }

    public function setTarteb(?int $tarteb): self
    {
        $this->tarteb = $tarteb;

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

    public function isOpenInNewTab(): ?bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): self
    {
        $this->openInNewTab = $openInNewTab;

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

    /**
     * @return Collection<int, BannerTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(BannerTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(BannerTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }

    public function getPlacementName(): ?string
    {
        return $this->placementName;
    }

}
