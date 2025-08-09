<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\BlogTag;
use App\CMSBundle\Form\BlogTagType;
use App\CMSBundle\Repository\BlogRepository;
use App\CMSBundle\Repository\BlogTagRepository;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("blog/tag")
 */
class BlogTagController extends AbstractController
{

    /**
     * Lists all blogTag entities.
     *
     * @Route("", name="blog_tag_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('cms/admin/blogTag/index.html.twig');
    }

    /**
     * Creates a new blogTag entity.
     *
     * @Route("/new", name="blog_tag_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $blogTag = new BlogTag();
        $form = $this->createForm(BlogTagType::class, $blogTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($blogTag);
            $this->em()->flush();
            if (in_array('text/javascript', $request->getAcceptableContentTypes())) {
                $return = ['error' => 0, 'message' => 'Successfully saved', 'object' => $blogTag->getObj()];

                return $this->json($return);
            }
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('blog_tag_index');
        }

        return $this->render('cms/admin/blogTag/new.html.twig', [
            'blogTag' => $blogTag,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing blogTag entity.
     *
     * @Route("/{id}/edit", name="blog_tag_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, BlogTag $blogTag): Response
    {
        $form = $this->createForm(BlogTagType::class, $blogTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('blog_tag_edit', ['id' => $blogTag->getId()]);
        }

        return $this->render('cms/admin/blogTag/edit.html.twig', [
            'blogTag' => $blogTag,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a blogTag entity.
     *
     * @Route("/{id}", name="blog_tag_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        BlogTag $blogTag,
        UserService $userService,
        BlogRepository $blogRepository
    ): Response {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('_token'))) {
            $this->addFlash("warning", "Invalid CSRF token");

            return $this->redirectToRoute('blog_tag_index');
        }

        if ($blogRepository->getNoOfBlogsByTag($blogTag) > 0) {
            $this->addFlash("error", "You can't remove this tag because it has blogs related to it");

            return $this->redirectToRoute('blog_tag_index');
        }
        $userName = $userService->getUserName();
        $blogTag->setDeletedBy($userName);
        $blogTag->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($blogTag);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('blog_tag_index');
    }

    /**
     * Lists all BlogTag entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="blog_tag_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, BlogTagRepository $blogTagRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $blogTagRepository->filter($search, true);
        $blogTags = $blogTagRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/blogTag/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "blogTags" => $blogTags,
        ]);
    }

}
