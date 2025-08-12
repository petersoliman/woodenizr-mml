<?php

namespace App\CMSBundle\Entity;

use App\CMSBundle\Entity\Translation\TestimonialTranslation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * Testimonial
 *
 * @ORM\Table(name="testimonial")
 * @ORM\Entity(repositoryClass="App\CMSBundle\Repository\TestimonialRepository")
 */
class Testimonial implements Translatable, DateTimeInterface
{

    use LocaleTrait,
        DateTimeTrait,
        VirtualDeleteTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="client", type="string")
     */
    protected ?string $client;

    /**
     * @ORM\Column(name="position", type="string", nullable=true)
     */
    protected ?string $position;

    /**
     * @ORM\Column(name="message", type="text")
     */
    protected string $message;

    /**
     * @var bool
     *
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @ORM\OneToMany(targetEntity="App\CMSBundle\Entity\Translation\TestimonialTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return Collection<int, TestimonialTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(TestimonialTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(TestimonialTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

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
