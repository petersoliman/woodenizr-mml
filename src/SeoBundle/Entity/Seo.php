<?php

namespace App\SeoBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use PN\SeoBundle\Entity\Seo as BaseSeo;
use PN\SeoBundle\Model\SeoTrait;

/**
 * Seo
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("seo", uniqueConstraints={@UniqueConstraint(name="slug_unique", columns={"slug", "seo_base_route_id", "deleted"})})
 * @ORM\Entity(repositoryClass="App\SeoBundle\Repository\SeoRepository")
 */
class Seo extends BaseSeo
{

    use SeoTrait;

    /**
     * @ORM\OneToMany(targetEntity="App\SeoBundle\Entity\Translation\SeoTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="LAZY")
     */
    protected Collection $translations;

    /**
     * @ORM\Column(name="canonical_url", type="string", length=500, nullable=true)
     */
    protected ?string $canonicalUrl = null;

    /**
     * @ORM\Column(name="robots", type="string", length=100, nullable=true)
     */
    protected ?string $robots = null;

    /**
     * @ORM\Column(name="pinterest_rich_pin", type="boolean", nullable=true)
     */
    protected ?bool $pinterestRichPin = null;

    /**
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    protected ?string $author = null;

    /**
     * @ORM\Column(name="twitter_site", type="string", length=255, nullable=true)
     */
    protected ?string $twitterSite = null;

    /**
     * @ORM\Column(name="twitter_creator", type="string", length=255, nullable=true)
     */
    protected ?string $twitterCreator = null;

    /**
     * @ORM\Column(name="whatsapp_image_width", type="integer", nullable=true)
     */
    protected ?int $whatsappImageWidth = null;

    /**
     * @ORM\Column(name="whatsapp_image_height", type="integer", nullable=true)
     */
    protected ?int $whatsappImageHeight = null;









    // Getters and Setters
    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    public function getRobots(): ?string
    {
        return $this->robots;
    }

    public function setRobots(?string $robots): self
    {
        $this->robots = $robots;
        return $this;
    }

    public function getPinterestRichPin(): ?bool
    {
        return $this->pinterestRichPin;
    }

    public function setPinterestRichPin(?bool $pinterestRichPin): self
    {
        $this->pinterestRichPin = $pinterestRichPin;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getTwitterSite(): ?string
    {
        return $this->twitterSite;
    }

    public function setTwitterSite(?string $twitterSite): self
    {
        $this->twitterSite = $twitterSite;
        return $this;
    }

    public function getTwitterCreator(): ?string
    {
        return $this->twitterCreator;
    }

    public function setTwitterCreator(?string $twitterCreator): self
    {
        $this->twitterCreator = $twitterCreator;
        return $this;
    }

    public function getWhatsappImageWidth(): ?int
    {
        return $this->whatsappImageWidth;
    }

    public function setWhatsappImageWidth(?int $whatsappImageWidth): self
    {
        $this->whatsappImageWidth = $whatsappImageWidth;
        return $this;
    }

    public function getWhatsappImageHeight(): ?int
    {
        return $this->whatsappImageHeight;
    }

    public function setWhatsappImageHeight(?int $whatsappImageHeight): self
    {
        $this->whatsappImageHeight = $whatsappImageHeight;
        return $this;
    }





}
