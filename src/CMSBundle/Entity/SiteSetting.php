<?php

namespace App\CMSBundle\Entity;

use App\CMSBundle\Enum\SiteSettingTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="site_setting")
 * @ORM\Entity(repositoryClass="App\CMSBundle\Repository\SiteSettingRepository")
 * @UniqueEntity(
 *     fields={"constantName"},
 *     errorPath="constantName",
 *     message="This constantName is already exist."
 * )
 */
class SiteSetting implements DateTimeInterface
{
    use DateTimeTrait;

    const  WEBSITE_HEAD_TAGS = "website-head-tags";
    const  GOOGLE_TAG_MANAGER_ID = "google-tag-manager-id";
    const  FACEBOOK_CHAT_PAGE_ID = "facebook-chat-page-id";
    const  FACEBOOK_PIXEL_ID = "facebook-pixel-id";
    const  WEBSITE_FAVICON = "website-favicon";
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="constant_name", type="string", unique=true)
     */
    private ?string $constantName = null;

    /**
     * @ORM\Column(name="title", type="string")
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="type", enumType=SiteSettingTypeEnum::class, type="string", length=255)
     */
    private ?SiteSettingTypeEnum $type = null;

    /**
     * @ORM\Column(name="manage_by_super_admin_only", type="boolean")
     */
    private bool $manageBySuperAdminOnly = false;

    /**
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private ?string $value = null;

    public function getTypeName(): ?string
    {
        return $this->getType()?->name();
    }

    public function getType(): ?SiteSettingTypeEnum
    {
        return $this->type;
    }

    public function setType(SiteSettingTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): string|bool|null
    {
        if ($this->getType() == SiteSettingTypeEnum::BOOLEAN) {
            if ($this->value == "0") {
                return false;
            }
            return true;
        }
        return $this->value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConstantName(): ?string
    {
        return $this->constantName;
    }

    public function setConstantName(string $constantName): static
    {
        $this->constantName = $constantName;

        return $this;
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

    public function isManageBySuperAdminOnly(): ?bool
    {
        return $this->manageBySuperAdminOnly;
    }

    public function setManageBySuperAdminOnly(bool $manageBySuperAdminOnly): static
    {
        $this->manageBySuperAdminOnly = $manageBySuperAdminOnly;

        return $this;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }


}
