<?php

namespace App\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("exchange_rate", uniqueConstraints={
 *     @UniqueConstraint(name="exchange_unique", columns={"source_currency_id", "target_currency_id"})
 *     }
 *  )
 * @ORM\Entity(repositoryClass="App\CurrencyBundle\Repository\ExchangeRateRepository")
 */
class ExchangeRate implements DateTimeInterface
{
    use DateTimeTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private ?Currency $sourceCurrency;

    /**
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private ?Currency $targetCurrency;

    /**
     * @var float
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="ratio", type="float")
     */
    private float $ratio;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRatio(): ?string
    {
        return $this->ratio;
    }

    public function setRatio(string $ratio): self
    {
        $this->ratio = $ratio;

        return $this;
    }

    public function getSourceCurrency(): ?Currency
    {
        return $this->sourceCurrency;
    }

    public function setSourceCurrency(?Currency $sourceCurrency): self
    {
        $this->sourceCurrency = $sourceCurrency;

        return $this;
    }

    public function getTargetCurrency(): ?Currency
    {
        return $this->targetCurrency;
    }

    public function setTargetCurrency(?Currency $targetCurrency): self
    {
        $this->targetCurrency = $targetCurrency;

        return $this;
    }


}
