<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="banner_translations")
 */
class BannerTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=50)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="sub_title", type="string", length=35,nullable=true)
     */
    private  ?string $subTitle = null;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private  ?string $url = null;

    /**
     * @ORM\Column(name="text", type="string", length=255, nullable=true)
     */
    private  ?string $text = null;

    /**
     * @ORM\Column(name="actionButton", type="string", length=20, nullable=true)
     */
    private  string $actionButton = 'عرض';

    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\Banner", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;


    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setSubTitle(?string $subTitle): self
    {
        $this->subTitle = $subTitle;

        return $this;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getActionButton(): ?string
    {
        return $this->actionButton;
    }

    public function setActionButton(?string $actionButton): self
    {
        $this->actionButton = $actionButton;

        return $this;
    }
}
