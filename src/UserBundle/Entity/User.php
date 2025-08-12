<?php

namespace App\UserBundle\Entity;

use App\UserBundle\Model\BaseUser;
use App\UserBundle\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="`user`")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User extends BaseUser
{
    use VirtualDeleteTrait;

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="full_name", type="string", length=255)
     */
    private string $fullName;

    /**
     * @ORM\Column(name="gender", type="string", length=20, nullable=true)
     */
    private ?string $gender = null;

    /**
     * @ORM\Column(name="birthdate", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $birthdate;

    /**
     * @Assert\Regex("/^[0-9\(\)\/\+ \-]+$/i")
     *
     * @ORM\Column(name="phone", type="string", nullable=true)
     */
    private ?string $phone;

    /**
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     */
    private ?string $facebookId;

    /**
     * @ORM\Column(name="google_id", type="string", length=255, nullable=true)
     */
    private ?string $googleId;

    /**
     * @ORM\Column(name="cart_item_no", type="integer", nullable=true)
     */
    private ?int $cartItemsNo = 0;

    /**
     * @ORM\Column(name="success_order_no", type="integer", nullable=true)
     */
    private ?int $successOrderNo = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }


    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): self
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getCartItemsNo(): ?int
    {
        return $this->cartItemsNo;
    }

    public function setCartItemsNo(?int $cartItemsNo): self
    {
        $this->cartItemsNo = $cartItemsNo;

        return $this;
    }

    public function getSuccessOrderNo(): ?int
    {
        return $this->successOrderNo;
    }

    public function setSuccessOrderNo(?int $successOrderNo): self
    {
        $this->successOrderNo = $successOrderNo;

        return $this;
    }


}
