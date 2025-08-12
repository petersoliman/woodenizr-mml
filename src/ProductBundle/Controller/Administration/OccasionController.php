<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductHasOccasion;
use App\ProductBundle\Form\Filter\ProductFilterType;
use App\ProductBundle\Form\OccasionType;
use App\ProductBundle\Messenger\UpdateOccasionProductNumber;
use App\ProductBundle\Repository\OccasionRepository;
use App\ProductBundle\Repository\ProductHasOccasionRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductService;
use App\SeoBundle\Repository\SeoRepository;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Occasion controller.
 *
 * @Route("/occasion")
 */
class OccasionController extends AbstractController
{

    /**
     * Lists all Occasion entities.
     *
     * @Route("/", name="occasion_index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->firewall();

        return $this->render("product/admin/occasion/index.html.twig");
    }

    /**
     * Displays a form to create a new Occasion entity.
     *
     * @Route("/new", name="occasion_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserService $userService): Response
    {
        $this->firewall();

        $occasion = new Occasion();
        $form = $this->createForm(OccasionType::class, $occasion);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($occasion);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');
            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('occasion_manage_product', ["id" => $occasion->getId()]);
            }

            return $this->redirectToRoute('occasion_index');
        }

        return $this->render("product/admin/occasion/new.html.twig", [
            'occasion' => $occasion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="occasion_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, OccasionRepository $occasionRepository, Occasion $occasion): Response
    {
        $this->firewall();

        $form = $this->createForm(OccasionType::class, $occasion);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            if ($occasion->isActive()) {
                $occasionRepository->clearAllActive();
            }
            $this->em()->persist($occasion);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('occasion_manage_product', ["id" => $occasion->getId()]);
            }

            return $this->redirectToRoute('occasion_index');
        }

        return $this->render("product/admin/occasion/edit.html.twig", [
            'occasion' => $occasion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="occasion_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Occasion $occasion): Response
    {
        $this->firewall();

        $occasion->setDeleted(new \DateTime());
        $occasion->setDeletedBy($userService->getUserName());
        $this->em()->persist($occasion);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully deleted');

        return $this->redirectToRoute('occasion_index');
    }

    /**
     * Active a Occasion entity.
     *
     * @Route("/active/{id}", name="occasion_active", methods={"POST"})
     */
    public function active(Request $request, OccasionRepository $occasionRepository, Occasion $occasion): Response
    {
        $this->firewall();

        $occasionRepository->clearAllActive();

        $occasion->setActive(true);
        $this->em()->persist($occasion);
        $this->em()->flush();

        return $this->redirectToRoute('occasion_index');
    }

    /**
     * @Route("/deactivate", name="occasion_deactivate", methods={"POST"})
     */
    public function deactivate(OccasionRepository $occasionRepository): Response
    {
        $this->firewall();

        $occasionRepository->clearAllActive();

        return $this->redirectToRoute('occasion_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="occasion_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, OccasionRepository $occasionRepository): Response
    {
        $this->firewall();

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $occasionRepository->filter($search, true);
        $occasions = $occasionRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/occasion/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "occasions" => $occasions,
        ]);
    }

    /**
     * @Route("/{id}/clone", name="occasion_clone", methods={"GET", "POST"})
     */
    public function clone(
        Request $request,
        SeoRepository $seoRepository,
        Occasion $occasion
    ): Response {
        $this->firewall();


        $newEntity = clone $occasion;
        $i = 0;
        do {
            if ($i == 0) {
                $slug = $newEntity->getSeo()->getSlug();
            } else {
                $slug = $newEntity->getSeo()->getSlug().'-'.$i;
            }
            $slugIfExist = $seoRepository->findOneBy([
                'seoBaseRoute' => $newEntity->getSeo()->getSeoBaseRoute()->getId(),
                'slug' => $slug,
                'deleted' => false,
            ]);
            $i++;
        } while ($slugIfExist != null);

        $newEntity->getSeo()->setSlug($slug);
        $form = $this->createForm(OccasionType::class, $newEntity);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            foreach ($occasion->getProductHasOccasions() as $productHasOccasion) {
                $newProductHasOccasion = new  ProductHasOccasion();
                $newProductHasOccasion->setOccasion($newEntity);
                $newProductHasOccasion->setProduct($productHasOccasion->getProduct());
                $newEntity->addProductHasOccasion($newProductHasOccasion);
            }

            $this->em()->persist($newEntity);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully cloned');

            return $this->redirectToRoute('occasion_manage_product', ["id" => $newEntity->getId()]);
        }


        return $this->render("product/admin/occasion/new.html.twig", [
            'occasion' => $occasion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lists all Coupon entities.
     *
     * @Route("/product/{id}", requirements={"id" = "\d+"}, name="occasion_manage_product", methods={"GET"})
     */
    public function manageProduct(Request $request, ProductService $productService, Occasion $occasion): Response
    {
        $this->firewall();

        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);

        return $this->render("product/admin/occasion/manageProduct.html.twig", [
            'occasion' => $occasion,
            "search" => $search,
            "filter_form" => $filterForm->createView(),
        ]);
    }

    /**
     * Lists all product entities.
     *
     * @Route("/product/data/table/{id}", requirements={"id" = "\d+"}, name="occasion_manage_product_datatable", methods={"GET"})
     */
    public function manageProductDatatable(
        Request $request,
        ProductRepository $productRepository,
        ProductService $productService,
        Occasion $occasion
    ): Response {
        $this->firewall();

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);
        if (Validate::not_null($srch['value'])) {
            $search->string = $srch['value'];
        }
        $search->ordr = $ordr[0];
        $search->deleted = 0;
        $search->currentOccasionId = $occasion->getId();

        $count = $productRepository->filter($search, true);
        $products = $productRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/occasion/manageProductDatatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "products" => $products,
        ]);
    }

    /**
     * add or update product tp occasion.
     *
     * @Route("/update-product/{id}", requirements={"id" = "\d+"}, name="occasion_manage_product_update_ajax", methods={"POST"})
     */
    public function addOrUpdateProductToOccasion(
        Request $request,
        ProductRepository $productRepository,
        ProductHasOccasionRepository $productHasOccasionRepository,
        Occasion $occasion
    ): Response {
        $this->firewall();

        $productId = $request->request->get('id');


        if (!is_numeric($productId)) {
            $type = $request->request->get('type');
            $productIds = json_decode($productId);
            $products = $productRepository->findBy(["id" => $productIds]);
            foreach ($products as $product) {
                $checkOccasionHasProduct = $productHasOccasionRepository->findOneBy([
                    "product" => $product,
                    "occasion" => $occasion,
                ]);
                if (isset($type) and $type == 'add') {
                    if (!$checkOccasionHasProduct) {
                        $this->addProductToOccasion($product, $occasion);
                    }
                } else {
                    if ($checkOccasionHasProduct) {
                        $this->em()->remove($checkOccasionHasProduct);
                    }
                }
            }

            $return = 1;
        } else {
            $product = $productRepository->find($productId);
            $return = $this->addOrDeleteProductInOccasion($productHasOccasionRepository, $product, $occasion);

        }
        $this->em()->flush();

        return $this->json(['value' => $return]);
    }

    private function addOrDeleteProductInOccasion(
        ProductHasOccasionRepository $productHasOccasionRepository,
        Product $product,
        Occasion $occasion
    ): int {

        $checkOccasionHasProduct = $productHasOccasionRepository->findOneBy([
            "product" => $product,
            "occasion" => $occasion,
        ]);
        if ($checkOccasionHasProduct) {
            $return = 0;
            $this->em()->remove($checkOccasionHasProduct);

        } else {
            $return = 1;
            $this->addProductToOccasion($product, $occasion);
        }

        return $return;
    }

    private function addProductToOccasion(Product $product, Occasion $occasion)
    {

        $productHasOccasion = new ProductHasOccasion();
        $productHasOccasion->setProduct($product);
        $productHasOccasion->setOccasion($occasion);
        $this->em()->persist($productHasOccasion);
    }

    /**
     * Remove all product from occasion entity.
     *
     * @Route("/clear-product/{id}", name="occasion_clear_products", methods={"POST"})
     */
    public function clearProduct(
        Request $request,
        MessageBusInterface $messageBus,
        ProductHasOccasionRepository $productHasOccasionRepository,
        Occasion $occasion
    ): Response {
        $this->firewall();

        $productHasOccasionRepository->removeProductByOccasion($occasion);
        $messageBus->dispatch(new UpdateOccasionProductNumber($occasion));

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }


        return $this->redirectToRoute('occasion_index');
    }

    private function firewall(): void
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN', 'ROLE_MANAGE_PRODUCTS']);
        if (!SystemConfiguration::ENABLE_OCCASION) {
            throw $this->createNotFoundException();
        }
    }

}
