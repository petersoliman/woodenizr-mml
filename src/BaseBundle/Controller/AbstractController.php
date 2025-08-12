<?php

namespace App\BaseBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;

abstract class AbstractController extends BaseController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    protected function em(): EntityManagerInterface
    {
        return $this->em;
    }

    protected function denyAccessUnlessGranted($attribute, $subject = null, string $message = 'Access Denied.'): void
    {
        if (is_string($attribute)) {
            parent::denyAccessUnlessGranted($attribute, $subject, $message);
        } elseif (is_array($attribute)) {
            $hasPermission = false;
            foreach ($attribute as $role) {
                $hasPermission = $this->isGranted($role) ? true : $hasPermission;
                if ($hasPermission === true) {
                    return;
                }
            }
            if (!$hasPermission) {
                throw $this->createAccessDeniedException($message);
            }
        }


    }
}