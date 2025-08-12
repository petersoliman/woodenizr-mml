<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Blog;
use App\CMSBundle\Form\BlogType;
use App\CMSBundle\Repository\BlogRepository;
use App\UserBundle\Model\UserInterface;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Blog controller.
 *
 * @Route("blog")
 */
class BlogController extends AbstractController
{

    /**
     * @Route("/", name="blog_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('cms/admin/blog/index.html.twig');
    }

    /**
     * @Route("/new", name="blog_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $blog = new Blog();
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($blog);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('post_set_images', ['id' => $blog->getPost()->getId(),]
            );
        }

        return $this->render('cms/admin/blog/new.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="blog_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Blog $blog): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('blog_index');
        }

        return $this->render('cms/admin/blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="blog_delete", methods={"DELETE"})
     */
    public function delete(Blog $blog, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $userName = $userService->getUserName();
        $blog->setDeletedBy($userName);
        $blog->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($blog);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('blog_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="blog_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, BlogRepository $blogRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $blogRepository->filter($search, true);
        $blogs = $blogRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/blog/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "blogs" => $blogs,
        ]);
    }

}
