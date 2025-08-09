<?php

namespace App\ProductBundle\Service;

use App\ECommerceBundle\Repository\CartHasProductPriceRepository;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PN\SeoBundle\Service\SeoService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductService
{
    private EntityManagerInterface $em;
    private UserService $userService;
    private SeoService $seoService;
    private CartHasProductPriceRepository $cartHasProductPriceRepository;

    public function __construct(
        EntityManagerInterface        $em,
        UserService                   $userService,
        SeoService                    $seoService,
        CartHasProductPriceRepository $cartHasProductPriceRepository
    )
    {
        $this->em = $em;
        $this->userService = $userService;
        $this->seoService = $seoService;
        $this->cartHasProductPriceRepository = $cartHasProductPriceRepository;
    }

    public function collectSearchData(FormInterface $form): \stdClass
    {
        $search = new \stdClass;
        $search->deleted = 0;
        $search->string = $form->get("str")->getData();
        $search->selectedCategory = ($form->get("category")->getData()) ? $form->get("category")->getData() : null;
        $search->publish = $form->get("publish")->getData();
        $search->featured = $form->get("featured")->getData();
        $search->newArrival = $form->get("newArrival")->getData();
        if ($form->has("collection")) {
            $search->collection = ($form->get("collection")->getData()) ? $form->get("collection")->getData() : null;
        }
        if ($form->has("occasion")) {
            $search->occasion = ($form->get("occasion")->getData()) ? $form->get("occasion")->getData() : null;
        }

        $search->categories = [];
        $categories = $form->get("category")->getData();
        if ($categories instanceof ArrayCollection and count($categories) > 0) {
            foreach ($categories as $category) {
                $search->categories = array_merge($search->categories, explode(",", $category->getConcatIds()));
            }
        }
        if ($categories instanceof Category) {
            $search->categories = explode(",", $categories->getConcatIds());
        }

        return $search;
    }

    public function deleteProduct(Product $product): void
    {
        $this->cartHasProductPriceRepository->deleteByProduct($product);
        $userName = $this->userService->getUserName();
        $product->setDeletedBy($userName);
        $product->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em->persist($product);
        $this->em->flush();
    }

    public function getBySlug(Request $request, ?string $slug, bool $redirect = true)
    {
        $entity = $this->seoService->getSlug($request, $slug, new Product(), redirect: $redirect);
        if ($entity instanceof RedirectResponse) {
            return $entity;
        }
        if (!$entity and $redirect) {
            throw new NotFoundHttpException();
        }
        if ($entity == null) {
            return false;
        }

        $isValid = $this->em->getRepository(Product::class)->isValidToShowInFrontEnd($entity);
        if (!$isValid) {
            return false;
        }

        return $entity;
    }
}
