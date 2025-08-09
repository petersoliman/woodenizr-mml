<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\DynamicPage;
use App\CMSBundle\Form\DynamicPageType;
use App\CMSBundle\Repository\DynamicPageRepository;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("dynamic-page")
 */
class DynamicPageController extends AbstractController
{

    /**
     * Lists all dynamicPage entities.
     *
     * @Route("/", name="dynamic_page_index", methods={"GET"})
     */
    public function index(): Response
    {
                return $this->render('cms/admin/dynamicPage/index.html.twig');
    }

    /**
     * @Route("/new", name="dynamic_page_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $dynamicPage = new DynamicPage();
        $form = $this->createForm(DynamicPageType::class, $dynamicPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($dynamicPage);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('post_set_images', ['id' => $dynamicPage->getPost()->getId()]);
        }

        return $this->render('cms/admin/dynamicPage/new.html.twig', [
            'dynamicPage' => $dynamicPage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="dynamic_page_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request,   DynamicPage $dynamicPage): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(DynamicPageType::class, $dynamicPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($dynamicPage);
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('dynamic_page_edit', ['id' => $dynamicPage->getId()]);
        }

        return $this->render('cms/admin/dynamicPage/edit.html.twig', [
            'dynamicPage' => $dynamicPage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="dynamic_page_delete", methods={"DELETE"})
     */
    public function delete(DynamicPage $dynamicPage): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $cantDeleted = [1];

        if (in_array($dynamicPage->getId(), $cantDeleted)) {
            $this->addFlash('error', 'Can not remove this dynamic page');

            return $this->redirectToRoute('dynamic_page_index');
        }
        $this->em()->remove($dynamicPage);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('dynamic_page_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="dynamic_page_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, DynamicPageRepository $dynamicPageRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];

        $count = $dynamicPageRepository->filter($search, true);
        $dynamicPages = $dynamicPageRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/dynamicPage/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "dynamicPages" => $dynamicPages,
        ]);
    }

}
