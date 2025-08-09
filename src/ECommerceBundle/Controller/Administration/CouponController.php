<?php

namespace App\ECommerceBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Entity\CouponHasProduct;
use App\ECommerceBundle\Form\CouponType;
use App\ECommerceBundle\Repository\CouponRepository;
use App\ProductBundle\Form\Filter\ProductFilterType;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductService;
use App\UserBundle\Entity\User;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Coupon controller.
 *
 * @Route("/coupon")
 */
class CouponController extends AbstractController
{

    /**
     * @Route("/", name="coupon_index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        return $this->render('eCommerce/admin/coupon/index.html.twig');
    }

    /**
     * @Route("/new", name="coupon_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $coupon = new Coupon();
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($coupon);
            $this->em()->flush();

            return $this->redirectToRoute('coupon_index');
        }

        return $this->render('eCommerce/admin/coupon/new.html.twig', [
            'coupon' => $coupon,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="coupon_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Coupon $coupon): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($coupon);
            $this->em()->flush();

            return $this->redirectToRoute('coupon_edit', ['id' => $coupon->getId()]);
        }

        return $this->render('eCommerce/admin/coupon/edit.html.twig', [
            'coupon' => $coupon,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="coupon_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Coupon $coupon): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $coupon->setDeletedBy($userService->getUserName());
        $coupon->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($coupon);
        $this->em()->flush();

        return $this->redirectToRoute('coupon_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="coupon_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, CouponRepository $couponRepository): Response
    {

        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $couponRepository->filter($search, true);
        $coupons = $couponRepository->filter($search, false, $start, $length);

        return $this->render("eCommerce/admin/coupon/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "coupons" => $coupons,
        ]);
    }

    /**
     * @Route("/product/{id}", requirements={"id" = "\d+"}, name="coupon_manage_product", methods={"GET"})
     */
    public function manageProduct(Request $request, ProductService $productService, Coupon $coupon): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);

        return $this->render('eCommerce/admin/coupon/manageProduct.html.twig', [
            "coupon" => $coupon,
            "search" => $search,
            "filter_form" => $filterForm->createView(),
        ]);
    }

    /**
     * @Route("/product/data/table/{id}", requirements={"id" = "\d+"}, name="coupon_manage_product_datatable", methods={"GET"})
     */
    public function manageProductDatatable(
        Request $request,
        ProductRepository $productRepository,
        ProductService $productService,
        Coupon $coupon
    ): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

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
        $search->currentCouponId = $coupon->getId();


        $count = $productRepository->filter($search, true);
        $products = $productRepository->filter($search, false, $start, $length);

        return $this->render("eCommerce/admin/coupon/manageProductDatatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "products" => $products,
        ]);
    }

    /**
     * @Route("/update-product/{id}", name="coupon_manage_product_update_ajax", methods={"POST"})
     */
    public function addOrUpdateProductToCoupon(
        Request $request,
        CouponRepository $couponRepository,
        ProductRepository $productRepository,
        Coupon $coupon
    ): Response {
        $productId = $request->request->get('id');

        if (!is_numeric($productId)) {
            $type = $request->request->get('type');
            $productIds = json_decode($productId);
            $products = $productRepository->findBy(["id" => $productIds]);
            foreach ($products as $product) {
                $checkCouponHasProduct = $couponRepository->checkCouponHasProduct($coupon, $product);
                if (isset($type) and $type == 'add') {
                    if (!$checkCouponHasProduct) {
                        $couponHasProduct = new CouponHasProduct();
                        $couponHasProduct->setCoupon($coupon);
                        $couponHasProduct->setProduct($product);
                        $coupon->addCouponHasProduct($couponHasProduct);
                        $this->em()->persist($coupon);
                    }
                } else {
                    if ($checkCouponHasProduct) {
                        $couponRepository->removeCouponHasProduct($coupon,
                            $product);
                    }
                }
            }
            $this->em()->flush();
            $return = 1;
        } else {
            $product = $productRepository->find($productId);
            $checkCouponHasProduct = $couponRepository->checkCouponHasProduct($coupon, $product);
            if ($checkCouponHasProduct) {
                $couponRepository->removeCouponHasProduct($coupon, $product);
                $return = 0;
            } else {
                $couponHasProduct = new CouponHasProduct();
                $couponHasProduct->setCoupon($coupon);
                $couponHasProduct->setProduct($product);
                $coupon->addCouponHasProduct($couponHasProduct);
                $this->em()->persist($coupon);
                $this->em()->flush();
                $return = 1;
            }
        }

        return $this->json(['value' => $return]);
    }

}
