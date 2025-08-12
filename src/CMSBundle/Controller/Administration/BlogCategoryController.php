<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\BlogCategory;
use App\CMSBundle\Form\BlogCategoryType;
use App\CMSBundle\Repository\BlogCategoryRepository;
use App\CMSBundle\Repository\BlogRepository;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("blog/category")
 */
class BlogCategoryController extends AbstractController
{

    /**
     * Lists all blogCategory entities.
     *
     * @Route("", name="blog_category_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('cms/admin/blogCategory/index.html.twig');
    }

    /**
     * Creates a new blogCategory entity.
     *
     * @Route("/new", name="blog_category_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $blogCategory = new BlogCategory();
        $form = $this->createForm(BlogCategoryType::class, $blogCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($blogCategory);
            $this->em()->flush();
            if (in_array('text/javascript', $request->getAcceptableContentTypes())) {
                $return = ['error' => 0, 'message' => 'Successfully saved', 'object' => $blogCategory->getObj()];

                return $this->json($return);
            }
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('blog_category_index');
        }

        return $this->render('cms/admin/blogCategory/new.html.twig', [
            'blogCategory' => $blogCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing blogCategory entity.
     *
     * @Route("/{id}/edit", name="blog_category_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, BlogCategory $blogCategory): Response
    {
        $form = $this->createForm(BlogCategoryType::class, $blogCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('blog_category_edit', ['id' => $blogCategory->getId()]);
        }

        return $this->render('cms/admin/blogCategory/edit.html.twig', [
            'blogCategory' => $blogCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="blog_category_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        BlogCategory $blogCategory,
        BlogRepository $blogRepository,
        UserService $userService
    ): Response {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('_token'))) {
            $this->addFlash("warning", "Invalid CSRF token");

            return $this->redirectToRoute('blog_category_index');
        }

        if ($blogRepository->getNoOfBlogsByCategory($blogCategory) > 0) {
            $this->addFlash("error", "You can't remove this category because it has blogs related to it");

            return $this->redirectToRoute('blog_category_index');
        }
        $userName = $userService->getUserName();
        $blogCategory->setDeletedBy($userName);
        $blogCategory->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($blogCategory);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('blog_category_index');
    }

    /**
     * Lists all BlogCategory entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="blog_category_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, BlogCategoryRepository $blogCategoryRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $blogCategoryRepository->filter($search, true);
        $blogCategories = $blogCategoryRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/blogCategory/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "blogCategories" => $blogCategories,
        ]);
    }
}
