<?php

namespace App\CMSBundle\Controller\FrontEnd;

use App\CMSBundle\Entity\BlogCategory;
use App\CMSBundle\Entity\BlogTag;
use App\CMSBundle\Repository\BlogRepository;
use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Blog;
use App\CMSBundle\Repository\FaqRepository;
use PN\ServiceBundle\Lib\Paginator;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\SeoBundle\Service\SeoService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Blog controller.
 *
 * @Route("faq")
 */
class FaqController extends AbstractController
{

    /**
     *
     * @Route("", name="fe_faq_index", methods={"GET"})
     */
    public function index(
        SeoPageRepository $seoPageRepository,
        FaqRepository $faqRepository
    ): Response {
        $seoPage = $seoPageRepository->findOneByType("faq");

        $search = new \stdClass;
        $search->ordr = ["column" => 0, "dir" => "ASC"];
        $search->publish = 1;
        $faqs = $faqRepository->filter($search);

        return $this->render('cms/frontEnd/faq/index.html.twig', [
            'seoPage' => $seoPage,
            'faqs' => $faqs,
        ]);
    }

}
