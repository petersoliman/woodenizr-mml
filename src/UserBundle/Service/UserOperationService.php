<?php

namespace App\UserBundle\Service;

use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserOperationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function deleteUser(User $user, $deletedBy): void
    {
        $rand = substr(md5(microtime()), rand(0, 26), 5);
        $user->setEmail($user->getEmail().'-del-'.$rand);
        if ($user->getFacebookId()) {
            $user->setFacebookId($user->getFacebookId().'-del-'.$rand);
        }
        $user->setEnabled(false);
        $user->setDeletedBy($deletedBy);
        $user->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em->persist($user);
        $this->em->flush();
    }

}
