<?php

namespace App\CMSBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Banner;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("banner")
 */
class BannerController extends AbstractController
{

    /**
     * @Route("/redirect/{id}", name="banner_redirect", methods={"GET"})
     */
    public function bannerRedirect(Request $request, Banner $banner): Response
    {
//        $banner->setHit($banner->getHit() + 1);
//        $this->em()->persist($banner);
//        $this->em()->flush();
        if (Validate::not_null($banner->getUrl()) and $banner->getUrl() != "#") {
            return $this->redirect($banner->getUrl());
        } else {
            return $this->redirectToRoute("fe_home");
        }
    }

}
