<?php

namespace App\ContentBundle\Repository;

use App\ContentBundle\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;
use PN\ContentBundle\Repository\PostRepository as BasePostRepository;

class PostRepository extends BasePostRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }
}
