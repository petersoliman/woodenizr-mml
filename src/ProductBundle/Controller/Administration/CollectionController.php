<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductHasCollection;
use App\ProductBundle\Form\CollectionType;
use App\ProductBundle\Form\Filter\ProductFilterType;
use App\ProductBundle\Messenger\UpdateCollectionProductNumber;
use App\ProductBundle\Repository\CollectionRepository;
use App\ProductBundle\Repository\ProductHasCollectionRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductService;
use App\SeoBundle\Repository\SeoRepository;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Collection controller.
 *
 * @Route("/collection")
 */
class CollectionController extends AbstractController
{

    /**
     * @Route("/", name="collection_index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->firewall();

        return $this->render("product/admin/collection/index.html.twig");
    }

    /**
     * @Route("/new", name="collection_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UploadImageService $uploadImageService, UserService $userService): Response
    {
        $this->firewall();

        $collection = new Collection();
        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $collection->setCreator($userName);
            $collection->setModifiedBy($userName);
            $this->em()->persist($collection);
            $this->em()->flush();

            //            $uploadImage = $this->uploadImage($request,$uploadImageService, $form, $collection);
            //            if (!$uploadImage) {
            //
            //                return $this->redirectToRoute('collection_edit',['id'=>$collection->getId()]);
            //            }

            $this->addFlash('success', 'Successfully saved');

            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('collection_manage_product', ["id" => $collection->getId()]);
            }

            return $this->redirectToRoute('collection_index');
        }

        return $this->render("product/admin/collection/new.html.twig", [
            'collection' => $collection,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="collection_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request $request,
        UploadImageService $uploadImageService,
        UserService $userService,
        Collection $collection
    ): Response {
        $this->firewall();

        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $collection->setModifiedBy($userName);
            $this->em()->flush();

            //            $uploadImage = $this->uploadImage($request,$uploadImageService, $form, $collection);
            //            if (!$uploadImage) {
            //
            //                return $this->redirectToRoute('collection_edit',['id'=>$collection->getId()]);
            //            }

            $this->addFlash('success', 'Successfully saved');

            if ($request->request->get("action") == "saveAndNext") {
                return $this->redirectToRoute('collection_manage_product', ["id" => $collection->getId()]);
            }

            return $this->redirectToRoute('collection_index');
        }

        return $this->render("product/admin/collection/edit.html.twig", [
            'collection' => $collection,
            'form' => $form->createView(),
        ]);
    }

    //    private function uploadImage(Request $request,UploadImageService $uploadImageService, FormInterface $form, Collection $entity)
    //    {
    //        if ($form->has("image")) {
    //            $file = $form->get("image")->get('file')->getData();
    //            if ($file == null) {
    //                return true;
    //            }
    //
    //           return $uploadImageService->uploadSingleImage($entity->getPost(), $file, 3, $request);
    //        }
    //
    //        return true;
    //    }

    /**
     * @Route("/{id}", name="collection_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Collection $collection): Response
    {
        $this->firewall();

        $collection->setDeleted(new \DateTime());
        $collection->setDeletedBy($userService->getUserName());
        $this->em()->persist($collection);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully deleted');

        return $this->redirectToRoute('collection_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="collection_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, CollectionRepository $collectionRepository): Response
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

        $count = $collectionRepository->filter($search, true);
        $collections = $collectionRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/collection/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "collections" => $collections,
        ]);
    }

    /**
     * @Route("/{id}/clone", name="collection_clone", methods={"GET", "POST"})
     */
    public function clone(
        Request $request,
        SeoRepository $seoRepository,
        Collection $collection
    ): Response {
        $this->firewall();


        $newEntity = clone $collection;
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
        $form = $this->createForm(CollectionType::class, $newEntity);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($collection->getProductHasCollections() as $productHasCollection) {
                $newProductHasCollection = new ProductHasCollection();
                $newProductHasCollection->setCollection($newEntity);
                $newProductHasCollection->setProduct($productHasCollection->getProduct());
                $newEntity->addProductHasCollection($newProductHasCollection);
            }

            $this->em()->persist($newEntity);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully cloned');

            return $this->redirectToRoute('collection_manage_product', ["id" => $newEntity->getId()]);
        }


        return $this->render("product/admin/collection/new.html.twig", [
            'collection' => $collection,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lists all Coupon entities.
     *
     * @Route("/product/{id}", requirements={"id" = "\d+"}, name="collection_manage_product", methods={"GET"})
     */
    public function manageProduct(Request $request, ProductService $productService, Collection $collection): Response
    {
        $this->firewall();
        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);

        return $this->render("product/admin/collection/manageProduct.html.twig", [
            'collection' => $collection,
            "search" => $search,
            "filter_form" => $filterForm->createView(),
        ]);
    }

    /**
     * Lists all product entities.
     *
     * @Route("/product/data/table/{id}", requirements={"id" = "\d+"}, name="collection_manage_product_datatable", methods={"GET"})
     */
    public function manageProductDatatable(
        Request $request,
        ProductService $productService,
        ProductRepository $productRepository,
        Collection $collection
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
        $search->currentCollectionId = $collection->getId();

        $count = $productRepository->filter($search, true);
        $products = $productRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/collection/manageProductDatatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "products" => $products,
        ]);
    }

    /**
     * add or update product tp collection.
     *
     * @Route("/update-product/{id}", requirements={"id" = "\d+"}, name="collection_manage_product_update_ajax", methods={"POST"})
     */
    public function addOrUpdateProductToCollection(
        Request $request,
        ProductRepository $productRepository,
        ProductHasCollectionRepository $productHasCollectionRepository,
        Collection $collection
    ): Response {
        $this->firewall();

        $productId = $request->request->get('id');

        if (!is_numeric($productId)) {
            $type = $request->request->get('type');
            $productIds = json_decode($productId);
            $products = $productRepository->findBy(["id" => $productIds]);
            foreach ($products as $product) {
                $checkCollectionHasProduct = $productHasCollectionRepository->findOneBy([
                    "product" => $product,
                    "collection" => $collection,
                ]);
                if (isset($type) and $type == 'add') {
                    if (!$checkCollectionHasProduct) {
                        $this->addProductToCollection($product, $collection);
                    }
                } else {
                    if ($checkCollectionHasProduct) {
                        $this->em()->remove($checkCollectionHasProduct);
                    }
                }
            }

            $return = 1;
        } else {
            $product = $productRepository->find($productId);
            $return = $this->addOrDeleteProductInCollection($productHasCollectionRepository, $product, $collection);

        }
        $this->em()->flush();

        return $this->json(['value' => $return]);
    }

    private function addOrDeleteProductInCollection(
        ProductHasCollectionRepository $productHasCollectionRepository,
        Product $product,
        Collection $collection
    ): int {

        $checkCollectionHasProduct = $productHasCollectionRepository->findOneBy([
            "product" => $product,
            "collection" => $collection,
        ]);
        if ($checkCollectionHasProduct) {
            $return = 0;
            $this->em()->remove($checkCollectionHasProduct);

        } else {
            $return = 1;
            $this->addProductToCollection($product, $collection);
        }

        return $return;
    }

    private function addProductToCollection(Product $product, Collection $collection)
    {
        $productHasCollection = new ProductHasCollection();
        $productHasCollection->setProduct($product);
        $productHasCollection->setCollection($collection);
        $this->em()->persist($productHasCollection);
    }

    /**
     * Remove all product from collection entity.
     *
     * @Route("/clear-product/{id}", name="collection_clear_products", methods={"POST"})
     */
    public function clearProduct(
        Request $request,
        MessageBusInterface $messageBus,
        ProductHasCollectionRepository $productHasCollectionRepository,
        Collection $collection
    ): Response {
        $this->firewall();

        $productHasCollectionRepository->removeProductByCollection($collection);

        $messageBus->dispatch(new UpdateCollectionProductNumber($collection));

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('collection_index');
    }

    private function firewall()
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN', 'ROLE_MANAGE_PRODUCTS']);
        if (!SystemConfiguration::ENABLE_COLLECTION) {
            throw $this->createNotFoundException();
        }
    }

}
