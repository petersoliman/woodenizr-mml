<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Form\CategoryType;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\CategoryService;
use App\ProductBundle\Service\CategoryWebsiteHeaderService;
use PN\MediaBundle\Entity\Image;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("category")
 */
class CategoryController extends AbstractController
{

    /**
     * @Route("/{parentCategory}", requirements={"parentCategory" = "\d+"}, name="category_index", methods={"GET"})
     */
    public function index(CategoryService $categoryService, Category $parentCategory = null): Response
    {
        $categoryParents = $categoryService->parentsByChild($parentCategory);

        return $this->render('product/admin/category/index.html.twig', [
            'parentCategory' => $parentCategory,
            'categoryParents' => $categoryParents,
        ]);
    }

    /**
     * @Route("/new/{parent}", requirements={"parent" = "\d+"}, name="category_new", methods={"GET", "POST"})
     */
    public function new(
        Request $request,
        CategoryService $categoryService,
        UploadImageService $uploadImageService,
        CategoryWebsiteHeaderService $categoryWebsiteHeaderService,
        CategoryRepository $categoryRepository,
        $parent = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        if ($parent != null) {
            $parent = $categoryRepository->find($parent);
            $category->setParent($parent);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $depth = $this->getDepth($category);
            $category->setDepth($depth);

            $levelOneCategory = $this->getLevelOne($category);
            $category->setLevelOne($levelOneCategory);

            $parentConcat = $this->getParentConcat($parent);
            $category->setParentConcatIds($parentConcat);

            $this->em()->persist($category);
            $this->em()->flush();


            $category->setConcatIds($category->getId());
            $this->updateParentConcatIds($category, $parent);

            $this->em()->persist($category);
            $this->em()->flush();
            $categoryWebsiteHeaderService->removeAllCache();

            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('attribute_index', ["id" => $category->getId()]);
            }

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $category);
            if ($uploadImage === false) {
                return $this->redirectToRoute('category_edit', ["id" => $category->getId()]);
            }


            $this->addFlash('success', 'Successfully saved');
            if ($category->getParent()) {
                return $this->redirectToRoute('category_index', ['parentCategory' => $category->getParent()->getId()]);
            }

            return $this->redirectToRoute('category_index');
        }
        $categoryParents = $categoryService->parentsByChild($parent);

        return $this->render('product/admin/category/new.html.twig', [
            'category' => $category,
            'categoryParents' => $categoryParents,
            'form' => $form->createView(),
        ]);
    }


    /**
     * Displays a form to edit an existing category entity.
     *
     * @Route("/{id}/edit", name="category_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request $request,
        CategoryService $categoryService,
        UploadImageService $uploadImageService,
        CategoryWebsiteHeaderService $categoryWebsiteHeaderService,
        UserService $userService,
        Category $category
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $category->setModifiedBy($userName);
            $this->em()->flush();
            $categoryWebsiteHeaderService->removeAllCache();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $category);
            if ($uploadImage === false) {

                return $this->redirectToRoute('category_edit', ['id' => $category->getId()]);
            }


            $this->addFlash('success', 'Successfully saved');
            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('attribute_index', ["id" => $category->getId()]);
            }

            if ($category->getParent()) {
                return $this->redirectToRoute('category_index', ['parentCategory' => $category->getParent()->getId()]);
            }

            return $this->redirectToRoute('category_index');
        }
        $categoryParents = $categoryService->parentsByChild($category->getParent());

        return $this->render('product/admin/category/edit.html.twig', [
            'category' => $category,
            'categoryParents' => $categoryParents,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a category entity.
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="category_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        UserService $userService,
        CategoryWebsiteHeaderService $categoryWebsiteHeaderService,
        ProductRepository $productRepository,
        Category $category
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        if (count($category->getChildren()) > 0) {
            $this->addFlash("error", "You can't delete this item!");
            if ($category->getParent()) {
                return $this->redirectToRoute('category_index', ["parentCategory" => $category->getParent()->getId()]);
            }

            return $this->redirectToRoute('category_index');
        }

        $search = new \stdClass();
        $search->deleted = 0;
        $search->category = $category->getId();
        $count = $productRepository->filter($search, true);
        if ($count > 0) {
            $this->addFlash("error",
                "You can't remove this category because $count or more products are attached to it");
            if ($category->getParent()) {
                return $this->redirectToRoute('category_index', ["parentCategory" => $category->getParent()->getId()]);
            }

            return $this->redirectToRoute('category_index');
        }
        if ($category->getParent()) {
            $top = $category->getParent();
            while ($top) {
                $concat = $top->getConcatIds();
                $concatArray = explode(',', $concat);
                $concatArray = array_merge(array_diff($concatArray, [$category->getId()]));
                $concatIds = implode(',', $concatArray);
                $top->setConcatIds($concatIds);
                $this->em()->persist($top);

                $top = $top->getParent();
            }
        }

        $userName = $userService->getUserName();
        $category->setDeletedBy($userName);
        $category->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($category);
        $this->em()->flush();

        $categoryWebsiteHeaderService->removeAllCache();

        if ($category->getParent()) {
            return $this->redirectToRoute('category_index', ["parentCategory" => $category->getParent()->getId()]);
        }

        return $this->redirectToRoute('category_index');
    }

    /**
     * Lists all Category entities.
     *
     * @Route("/data/table/{id}", requirements={"id" = "\d+"}, defaults={"_format": "json"}, name="category_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, CategoryRepository $categoryRepository, $id = null): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN', 'ROLE_MANAGE_PRODUCTS']);

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $search = new \stdClass;
        $search->deleted = 0;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->parent = "";
        if ($id) {
            $parentCategory = $categoryRepository->find($id);
            $search->parent = $parentCategory->getId();
        }
        $count = $categoryRepository->filter($search, true);
        $categories = $categoryRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/category/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "categories" => $categories,
            "maximalDepthLevel" => SystemConfiguration::CATEGORY_MAXIMAL_DEPTH_LEVEL,
        ]);
    }

    private function uploadImage(
        Request $request,
        UploadImageService $uploadImageService,
        FormInterface $form,
        Category $entity
    ): bool|string|Image {
        $file = $form->get("image")->get("file")->getData();
        if (!$file instanceof UploadedFile) {
            return true;
        }

        return $uploadImageService->uploadSingleImage($entity, $file, 101, $request);
    }

    private function getDepth(Category $category): int
    {
        $depth = 1;
        if ($category->getParent() == null) {
            return $depth;
        }

        $parent = $category->getParent();
        while ($parent != null) {
            $parent = $parent->getParent();
            $depth++;
        }

        return $depth;
    }

    private function getLevelOne(Category $category): Category
    {
        $levelOneCategory = $category->getParent();
        if (!$levelOneCategory) {
            return $category;
        }

        while ($levelOneCategory->getParent()) {
            $levelOneCategory = $levelOneCategory->getParent();
        }

        return $levelOneCategory;
    }

    private function getParentConcat(Category $parent = null): ?string
    {
        if ($parent == null) {
            return null;
        }
        $parentConcat = [];
        $parentCategory = $parent;
        while ($parentCategory) {
            $parentConcat[] = $parentCategory->getId();
            $parentCategory = $parentCategory->getParent();
        }

        return implode(",", $parentConcat);
    }

    private function updateParentConcatIds(Category $category, Category $parent = null): void
    {
        if ($parent == null) {
            return;
        }

        $top = $parent;
        while ($top) {
            $concat = $top->getConcatIds();
            $concat .= ",".$category->getId();
            $top->setConcatIds($concat);
            $this->em()->persist($top);
            $top = $top->getParent();
        }
        $this->em()->flush();

    }
}
