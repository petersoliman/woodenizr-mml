<?php

namespace App\CMSBundle\Entity\Translation;

use App\CMSBundle\Entity\Service;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

;

/**
 * @ORM\Entity
 * @ORM\Table(name="service_translations")
 */
class ServiceTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private ?string $title = null;

    /**
     *
     * @ORM\Column(name="contact_text", type="text", length=300, nullable=true)
     */
    private ?string $contactText = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\Service", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Service
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getContactText(): ?string
    {
        return $this->contactText;
    }

    public function setContactText(?string $contactText): self
    {
        $this->contactText = $contactText;

        return $this;
    }
}
