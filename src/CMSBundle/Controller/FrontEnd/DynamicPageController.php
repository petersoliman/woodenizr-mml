<?php

namespace App\CMSBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\DynamicPage;
use App\CMSBundle\Repository\DynamicPageRepository;
use PN\SeoBundle\Service\SeoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * DynamicPage controller.
 *
 * @Route("page")
 */
class DynamicPageController extends AbstractController
{

    /**
     * @Route("/terms-condition", name="fe_terms_condition", methods={"GET"})
     */
    public function terms(DynamicPageRepository $dynamicPageRepository): Response
    {
        $page = $dynamicPageRepository->find(1);

        if (!$page) {
            throw $this->createNotFoundException();
        }

        return $this->render('cms/frontEnd/dynamicPage/page.html.twig', [
            'page' => $page,
        ]);
    }

    /**
     * @Route("/page/{slug}", name="fe_dynamic_page_show", methods={"GET"})
     */
    public function show(Request $request, SeoService $seoService, string $slug): Response
    {
        $page = $seoService->getSlug($request, $slug, new DynamicPage());
        if ($page instanceof RedirectResponse) {
            return $page;
        }
        if (!$page) {
            throw $this->createNotFoundException();
        }

        return $this->render('cms/frontEnd/dynamicPage/page.html.twig', [
            'page' => $page,
        ]);
    }
}
