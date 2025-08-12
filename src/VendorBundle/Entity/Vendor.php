<?php

namespace App\VendorBundle\Entity;

use App\ContentBundle\Entity\Post;
use App\MediaBundle\Entity\Image;
use App\SeoBundle\Entity\Seo;
use App\VendorBundle\Entity\Translation\VendorTranslation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("vendor")
 * @ORM\Entity(repositoryClass="App\VendorBundle\Repository\VendorRepository")
 */
class Vendor implements Translatable, DateTimeInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;


    /**
     * @ORM\OneToOne(targetEntity="App\SeoBundle\Entity\Seo", cascade={"persist", "remove" })
     */
    private ?Seo $seo = null;

    /**
     * Description
     * @ORM\OneToOne(targetEntity="App\ContentBundle\Entity\Post", cascade={"persist", "remove" })
     */
    private ?Post $post = null;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="email", type="string", length=180, nullable=true)
     * @Assert\NotBlank
     * @Assert\Email(message="The email '{{ value }}' is not a valid email")
     * @Assert\Length(
     *     min="2",
     *     max="120",
     *     minMessage="Your email must be at least {{ limit }} characters long",
     *     maxMessage="Your email cannot be longer than {{ limit }} characters",
     * )
     */
    private ?string $email = null;

    /**
     * @ORM\Column(name="company_registration_number", type="string",nullable=true)
     */
    private ?string $companyRegistrationNumber = null;

    /**
     * @ORM\Column(name="sales_tax_number", type="string", length=11,nullable=true)
     */
    private ?string $salesTaxNumber = null;

    /**
     * @ORM\Column(name="bank_name", type="string",length=50,nullable=true)
     */
    private ?string $bankName = null;

    /**
     * @ORM\Column(name="bank_account_swift_code", type="string",length=50, nullable=true)
     */
    private ?string $bankAccountSwiftCode = null;
    /**
     * @ORM\Column(name="bank_account_iban", type="string",length=50, nullable=true)
     */
    private ?string $bankAccountIBAN = null;

    /**
     * @ORM\Column(type="string",length=50,nullable=true)
     */
    private ?string $bankBranch = null;

    /**
     * @ORM\Column(name="bank_account_no", type="string",length=50,nullable=true)
     */
    private ?string $bankAccountNo = null;

    /**
     * @ORM\Column(name="bank_account_holder_name", type="string",length=225,nullable=true)
     */
    private ?string $bankAccountHolderName = null;


    /**
     * @ORM\Column(name="commission_percentage", type="float")
     */
    private float $commissionPercentage = 0;

    /**
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = false;

    /**
     * @ORM\OneToOne(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     */
    private ?Image $image = null;

    /**
     * @ORM\OneToMany(targetEntity="App\VendorBundle\Entity\StoreAddress", mappedBy="vendor")
     */
    private Collection $storeAddresses;

    /**
     * @ORM\OneToMany(targetEntity="App\VendorBundle\Entity\Translation\VendorTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private $translations;

    public function __construct()
    {
        $this->storeAddresses = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }


    public function __toString()
    {
        return $this->getTitle();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): static
    {
        $this->publish = $publish;

        return $this;
    }

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): static
    {
        $this->seo = $seo;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, StoreAddress>
     */
    public function getStoreAddresses(): Collection
    {
        return $this->storeAddresses;
    }

    public function addStoreAddress(StoreAddress $storeAddress): static
    {
        if (!$this->storeAddresses->contains($storeAddress)) {
            $this->storeAddresses->add($storeAddress);
            $storeAddress->setVendor($this);
        }

        return $this;
    }

    public function removeStoreAddress(StoreAddress $storeAddress): static
    {
        if ($this->storeAddresses->removeElement($storeAddress)) {
            // set the owning side to null (unless already changed)
            if ($storeAddress->getVendor() === $this) {
                $storeAddress->setVendor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VendorTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(VendorTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(VendorTranslation $translation): static
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }

    public function getCommissionPercentage(): ?float
    {
        return $this->commissionPercentage;
    }

    public function setCommissionPercentage(float $commissionPercentage): static
    {
        $this->commissionPercentage = $commissionPercentage;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCompanyRegistrationNumber(): ?string
    {
        return $this->companyRegistrationNumber;
    }

    public function setCompanyRegistrationNumber(?string $companyRegistrationNumber): static
    {
        $this->companyRegistrationNumber = $companyRegistrationNumber;

        return $this;
    }

    public function getSalesTaxNumber(): ?string
    {
        return $this->salesTaxNumber;
    }

    public function setSalesTaxNumber(?string $salesTaxNumber): static
    {
        $this->salesTaxNumber = $salesTaxNumber;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): static
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getBankAccountSwiftCode(): ?string
    {
        return $this->bankAccountSwiftCode;
    }

    public function setBankAccountSwiftCode(?string $bankAccountSwiftCode): static
    {
        $this->bankAccountSwiftCode = $bankAccountSwiftCode;

        return $this;
    }

    public function getBankAccountIBAN(): ?string
    {
        return $this->bankAccountIBAN;
    }

    public function setBankAccountIBAN(?string $bankAccountIBAN): static
    {
        $this->bankAccountIBAN = $bankAccountIBAN;

        return $this;
    }

    public function getBankBranch(): ?string
    {
        return $this->bankBranch;
    }

    public function setBankBranch(?string $bankBranch): static
    {
        $this->bankBranch = $bankBranch;

        return $this;
    }

    public function getBankAccountNo(): ?string
    {
        return $this->bankAccountNo;
    }

    public function setBankAccountNo(?string $bankAccountNo): static
    {
        $this->bankAccountNo = $bankAccountNo;

        return $this;
    }

    public function getBankAccountHolderName(): ?string
    {
        return $this->bankAccountHolderName;
    }

    public function setBankAccountHolderName(?string $bankAccountHolderName): static
    {
        $this->bankAccountHolderName = $bankAccountHolderName;

        return $this;
    }


}
