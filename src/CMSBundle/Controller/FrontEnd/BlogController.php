<?php

namespace App\CMSBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Blog;
use App\CMSBundle\Entity\BlogCategory;
use App\CMSBundle\Entity\BlogTag;
use App\CMSBundle\Repository\BlogRepository;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\SeoBundle\Service\SeoService;
use PN\ServiceBundle\Lib\Paginator;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * Lists all Blog entities.
     *
     * @Route("/{page}",requirements={"page" = "\d+"}, name="fe_blog_index", methods={"GET"})
     * @Route("/category/{slug}/{page}",requirements={"page" = "\d+"}, name="fe_blog_category", methods={"GET"})
     * @Route("/tag/{slug}/{page}",requirements={"page" = "\d+"}, name="fe_blog_tag", methods={"GET"})
     */
    public function index(
        Request           $request,
        BlogRepository    $blogRepository,
        SeoPageRepository $seoPageRepository,
        SeoService        $seoService,
        int               $page = 1,
        ?string           $slug = null
    ): Response
    {
        $seoPage = null;

        $search = new \stdClass;
        $search->ordr = ["column" => 1, "dir" => "DESC"];
        $search->deleted = 0;
        $search->publish = 1;

        $routeName = $request->get("_route");
        if ($routeName == "fe_blog_category") {
            if ($slug == null) {
                throw $this->createNotFoundException();
            }
            $category = $seoService->getSlug($request, $slug, new BlogCategory());
            if (!$category) {
                throw $this->createNotFoundException();
            }
            if ($category instanceof RedirectResponse) {
                return $category;
            }
            $search->category = $category->getId();
            $seoPage = $category;

        } elseif ($routeName == "fe_blog_tag") {
            if ($slug == null) {
                throw $this->createNotFoundException();
            }
            $tag = $seoService->getSlug($request, $slug, new BlogTag());
            if (!$tag) {
                throw $this->createNotFoundException();
            }
            if ($tag instanceof RedirectResponse) {
                return $tag;
            }
            $search->tag = $tag->getId();
            $seoPage = $tag;
        }

        if ($seoPage == null) {
            $seoPage = $seoPageRepository->findOneByType("blogs");
        }

        if ($request->query->has("content")) {
            $request->query->remove("content");

            $count = $blogRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 8);
            $blogs = ($count > 0) ? $blogRepository->filter($search, false, $paginator->getLimitStart(),
                $paginator->getPageLimit()) : [];
            return $this->render("cms/frontEnd/blog/_content.html.twig", [
                'blogs' => $blogs,
                'paginator' => $paginator->getPagination(),
            ]);
        }
        return $this->render('cms/frontEnd/blog/index.html.twig', [
            'seoPage' => $seoPage,

        ]);
    }

    /**
     * @Route("/{slug}", name="fe_blog_show", methods={"GET"})
     */
    public function show(
        Request        $request,
        SeoService     $seoService,
        BlogRepository $blogRepository,
        string         $slug
    ): Response
    {
        $blog = $seoService->getSlug($request, $slug, new Blog());
        if ($blog instanceof RedirectResponse) {
            return $blog;
        }
        if (!$blog) {
            throw $this->createNotFoundException();
        }

        return $this->render('cms/frontEnd/blog/show.html.twig', [
            'blog' => $blog,
            'relatedBlogs' => $this->getRelatedBlogs($blog, $blogRepository),
        ]);
    }

    private function getRelatedBlogs(Blog $blog, BlogRepository $blogRepository)
    {
        $search = new \stdClass;
        $search->ordr = ["column" => 0, "dir" => "DESC"];
        $search->deleted = 0;
        $search->publish = 1;
        $search->notId = $blog->getId();

        return $blogRepository->filter($search, false, 0, 3);
    }
}
