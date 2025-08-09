<?php

namespace App\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use PN\ContentBundle\Entity\Post as BasePost;
use PN\ContentBundle\Model\PostTrait;
use PN\LocaleBundle\Model\Translatable;

/**
 * Post
 * @ORM\Table(name="post")
 * @ORM\Entity(repositoryClass="App\ContentBundle\Repository\PostRepository")
 */
class Post extends BasePost implements Translatable
{

    use PostTrait;

    /**
     * @ORM\OneToMany(targetEntity="App\ContentBundle\Entity\Translation\PostTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true, fetch="LAZY")
     */
    protected $translations;


}
