<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="testimonial_translations")
 */
class TestimonialTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="client", type="string", length=255, nullable=true)
     */
    private  ?string $client=null;

    /**
     * @ORM\Column(name="position", type="string", length=255, nullable=true)
     */
    private  ?string $position;

    /**
     * @ORM\Column(name="message", type="text", length=255, nullable=true)
     */
    private ?string $message=null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\Testimonial", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(?string $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }
}
