<?php

namespace App\CMSBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Entity\Language;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

;

/**
 * @ORM\Entity
 * @ORM\Table(name="faq_translations")
 */
class FaqTranslation extends TranslationEntity implements EditableTranslation
{

    /**
     * @ORM\Column(name="question", type="text", nullable=true)
     */
    private ?string $question = null;

    /**
     * @var string
     *
     * @ORM\Column(name="answer", type="text", nullable=true)
     */
    private ?string $answer = null;

    /**
     * @var
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\CMSBundle\Entity\Faq", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    public function setQuestion(?string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

}
