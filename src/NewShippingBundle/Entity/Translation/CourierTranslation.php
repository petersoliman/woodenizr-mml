<?php

namespace App\NewShippingBundle\Entity\Translation;

use App\NewShippingBundle\Entity\Courier;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\TranslationEntity;
use PN\LocaleBundle\Model\EditableTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="courier_translations")
 */
class CourierTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected ?string $title = null;


    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\Courier", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected Language $language;

    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

}
