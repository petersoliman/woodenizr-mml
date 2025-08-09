<?php

namespace App\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use PN\MediaBundle\Entity\Image as BaseImage;
use PN\MediaBundle\Model\ImageInterface;
use PN\MediaBundle\Model\ImageTrait;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("image")
 * @ORM\Entity(repositoryClass="App\MediaBundle\Repository\ImageRepository")
 */
class Image extends BaseImage implements ImageInterface
{
    const TYPE_COVER_PHOTO = 3;

    use ImageTrait;

    /**
     * @ORM\ManyToMany(targetEntity="\App\ContentBundle\Entity\Post", mappedBy="images")
     */
    protected $posts;


    
    /**
     * @ORM\PreRemove
     */
    public function preRemove()
    {
        $this->removeUpload();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist()
     */
    public function updatedTimestamps()
    {
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }
}
