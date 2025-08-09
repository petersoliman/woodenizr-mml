<?php

namespace App\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="search_keyword")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\SearchKeywordsRepository")
 */
class SearchKeyword
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="keyword", type="string", length=150)
     */
    private ?string $keyword = null;

    /**
     * @var string
     *
     * @ORM\Column(name="canonical_keyword", type="string", length=150)
     */
    private ?string $canonicalKeyword= null;


    /**
     * @ORM\Column(name="ip", type="string", length=50)
     */
    private ?string $ip=null;

    /**
     * @ORM\Column(name="created", type="datetime")
     */
    private \DateTimeInterface $created;

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     */
    public function updatedTimestamps(): void
    {
        $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): self
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getCanonicalKeyword(): ?string
    {
        return $this->canonicalKeyword;
    }

    public function setCanonicalKeyword(string $canonicalKeyword): self
    {
        $this->canonicalKeyword = $canonicalKeyword;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

}
