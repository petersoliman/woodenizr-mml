<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductFavorite;
use App\ProductBundle\Repository\ProductFavoriteRepository;
use App\UserBundle\Entity\User;
use PN\SeoBundle\Service\SeoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{

    /**
     * @Route("/add-to-favorite/{slug}", name="fe_product_add_to_favorite_ajax", methods={"POST"})
     */
    public function addToFavoriteAction(
        Request                   $request,
        TranslatorInterface       $translator,
        SeoService                $seoService,
        ProductFavoriteRepository $productFavoriteRepository,
        string                    $slug
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => "You must login", "isFavorite" => false]);
        }

        $product = $seoService->getSlug($request, $slug, new Product());
        if (!$product) {
            return $this->json(["error" => true, "message" => "Invalid Product", "isFavorite" => false]);
        }

        $productFavorite = $productFavoriteRepository->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);
        if ($productFavorite) {
            $isFavorite = false;
            $this->em()->remove($productFavorite);
        } else {
            $isFavorite = true;
            $productFavorite = new ProductFavorite();
            $productFavorite->setProduct($product);
            $productFavorite->setUser($user);
            $this->em()->persist($productFavorite);
        }
        $this->em()->flush();

        $message = "Added to wishlist";
        if (!$isFavorite) {
            $message = "Removed from wishlist";

        }

        return $this->json(["error" => false, "isFavorite" => $isFavorite, "message" => $message]);
    }
}
