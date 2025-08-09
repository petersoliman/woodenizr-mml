<?php

namespace App\NewShippingBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use App\NewShippingBundle\Entity\ShippingTime;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\TranslationEntity;
use PN\LocaleBundle\Model\EditableTranslation;

/**
 * @ORM\Table(name="shipping_time_translation")
 * @ORM\Entity
 */
class ShippingTimeTranslation extends TranslationEntity implements EditableTranslation
{


    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    protected ?string $name = null;


    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\ShippingTime", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected Language $language;


    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
