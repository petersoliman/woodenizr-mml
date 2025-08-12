<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;


/**
 * @ORM\Entity
 * @ORM\Table(name="team_translations")
 */
class TeamTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="position", type="string", length=255, nullable=true)
     */
    private ?string $position = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\Team", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }
}
