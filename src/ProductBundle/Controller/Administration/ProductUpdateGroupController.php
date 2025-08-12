<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Entity\CouponHasProduct;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Form\Filter\ProductFilterType;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Date;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Product controller.
 *
 * @Route("/update-group")
 */
class ProductUpdateGroupController extends AbstractController
{

    private ?string $username = null;
    private UserService $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        parent::__construct($em);
        $this->userService = $userService;
    }

    /**
     * Lists all product entities.
     *
     * @Route("/{page}", requirements={"page" = "\d+"}, name="product_group_update_index", methods={"GET"})
     */
    public function index(
        Request $request,
        ProductRepository $productRepository,
        ProductService $productService,
        $page = 1
    ): Response {
        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);


        $count = $productRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 50);
        $products = $productRepository->filter($search, $count = false,
            $paginator->getLimitStart(), $paginator->getPageLimit());

        return $this->render('product/admin/productUpdateGroup/index.html.twig', [
            'search' => $search,
            "filter_form" => $filterForm->createView(),
            'products' => $products,
            'paginator' => $paginator->getPagination(),
        ]);
    }

    /**
     * @Route("/group-update/action", name="product_group_update_action", methods={"GET"})
     */
    public function groupUpdate(
        Request $request,
        ProductService $productService,
        ProductRepository $productRepository
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);


        $session = $request->getSession();

        if ($session->has('productSelected') and count($session->get('productSelected')) > 0) {
            $productSelected = $session->get('productSelected');
            $search = new \stdClass();
            $search->ids = $productSelected;
            $products = $productRepository->filter($search, false);
        } elseif ($request->get('filter') == '1') {
            $filterForm = $this->createForm(ProductFilterType::class);
            $filterForm->handleRequest($request);
            $search = $productService->collectSearchData($filterForm);
            $products = $productRepository->filter($search, false);

            $productSelected = [];
            foreach ($products as $product) {
                $productSelected[] = $product->getId();
            }
            $session->set('productSelected', $productSelected);
        } else {
            $this->addFlash('error', 'Please add some products');

            return $this->redirectToRoute('product_group_update_index');
        }


        return $this->render("product/admin/productUpdateGroup/groupUpdate.html.twig", [
            'products' => $products,
        ]);
    }

    /**
     * Lists all Product entities.
     *
     * @Route("/group-update/session-ajax", name="product_group_update_session_ajax", methods={"POST"})
     */
    public function productGroupUpdateSessionAjax(Request $request): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $session = $request->getSession();
        $id = $request->request->get('id');
        if ($session->has('productSelected')) {
            $productSelected = $session->get('productSelected');
        } else {
            $productSelected = [];
        }

        if (is_numeric($id)) {
            if (!in_array($id, $productSelected)) { // add product in array
                array_push($productSelected, $id);
            } else { // remove product in array
                $key = array_search($id, $productSelected);
                unset($productSelected[$key]);
            }
        } else {
            $type = $request->request->get('type');
            $ids = json_decode($id);
            foreach ($ids as $id) {
                if (isset($type) and $type == 'add') {
                    if (!in_array($id, $productSelected)) {
                        array_push($productSelected, $id);
                    }
                } else {
                    if (in_array($id, $productSelected)) {
                        $key = array_search($id, $productSelected);
                        unset($productSelected[$key]);
                    }
                }
            }
        }
        $session->set('productSelected', $productSelected);

        return $this->json($productSelected);
    }

    /**
     * Add a Product Price entity.
     *
     * @Route("/group-update/update", name="product_group_update_action_update", methods={"POST"})
     */
    public function productGroupUpdate(Request $request,ProductRepository $productRepository, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $session = $request->getSession();


        $type = $request->request->get('type');
        $data = $request->request->get('data');


        if (!Validate::not_null($type)) {
            $this->addFlash('error', 'Error in Type');

            return $this->redirectToRoute('product_group_update_action');
        }

        $productSelected = $session->get('productSelected');
        if (count($productSelected) == 0) {
            $this->addFlash('error', 'Please add some products');

            return $this->redirectToRoute('product_group_update_action');
        }

        $entities = [];
        foreach ($productSelected as $value) {
            if (Validate::not_null($value)) {
                $product = $productRepository->find($value);
                $entities[] = $product;
            }
        }
        $userName = $userService->getUserName();
        $n = 0;
        if ($type == 'checkbox') {
            foreach ($entities as $entity) {
                $n++;
                $this->updateCheckboxes($entity, $data);
            }
        } elseif ($type == 'promotion') {
            if (!isset($data['removeDiscount'])) {
                if (isset($data['discount']) and (!Validate::not_null($data['discount']) or !is_numeric($data['discount']))) {
                    $this->addFlash('error', 'Please enter the promotion discount');

                    return $this->redirectToRoute('product_group_update_action');
                }
                if (Validate::not_null($data['expiryDate']) and !Validate::date($data['expiryDate'])) {
                    $this->addFlash('error', 'Please enter a valid promotion expiry date');

                    return $this->redirectToRoute('product_group_update_action');
                }
            }
            $promotionalExpiryDate = null;
            if (isset($data['expiryDate']) and Validate::not_null($data['expiryDate'])) {
                $promotionalExpiryDate = Date::convertDateFormat($data['expiryDate'], Date::DATE_FORMAT3,
                    Date::DATE_FORMAT2);
                $promotionalExpiryDate = new \DateTime($promotionalExpiryDate);
            }

            foreach ($entities as $entity) {
                $n++;
                $this->updatePromotionPrice($entity, $data, $promotionalExpiryDate);
            }
        } elseif ($type == 'content') {
            foreach ($entities as $entity) {
                $n++;
                $this->updateContent($entity, $data);
            }
        }
        $this->em()->flush();

        $this->addFlash('success', $n.' Products updated successfully');
        if ($request->request->get('action') == "saveAndNext") {
            return $this->redirectToRoute('product_group_update_action');
        }
        $session->remove('productSelected');

        return $this->redirectToRoute('product_group_update_index');
    }

    private function getUsername(): ?string
    {
        if ($this->username == null) {
            return $this->username = $this->userService->getUserName();
        }

        return $this->username;
    }

    private function updateCheckboxes(Product $product, $data)
    {

        if (isset($data['featured'])) {
            $product->setFeatured(true);
        } elseif (isset($data['notFeatured'])) {
            $product->setFeatured(false);
        }
        if (isset($data['publish'])) {
            $product->setPublish(true);
        } elseif (isset($data['unpublish'])) {
            $product->setPublish(false);
        }

        if (isset($data['premium'])) {
            $product->setPremium(true);
        } elseif (isset($data['notPremium'])) {
            $product->setPremium(false);
        }

        if (isset($data['newArrival'])) {
            $product->setNewArrival(true);
        } elseif (isset($data['notNewArrival'])) {
            $product->setNewArrival(false);
        }
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    private function updateCoupon(Product $product, Coupon $coupon)
    {
        $checkCouponHasProduct = $this->em()->getRepository('ECommerceBundle:Coupon')->checkCouponHasProduct($coupon->getId(),
            $product->getId());
        if (!$checkCouponHasProduct) {
            $couponHasProduct = new CouponHasProduct();
            $couponHasProduct->setCoupon($coupon);
            $couponHasProduct->setProduct($product);
            $coupon->addCouponHasProduct($couponHasProduct);
            $this->em()->persist($coupon);
        }
    }

    private function updatePromotionPrice(Product $product, $data, \DateTime $promotionalExpiryDate = null)
    {


        //        foreach ($product->getPrices() as $price) {
        //            if (isset($data['removeDiscount'])) {
        //                $price->setPromotionalPrice(null);
        //                $price->setPromotionalExpiryDate(null);
        //            } else {
        //                $promotionalPrice = $price->getPrice() - ($price->getPrice() / 100) * $data['discount'];
        //                $price->setPromotionalPrice($promotionalPrice);
        //                $price->setPromotionalExpiryDate($promotionalExpiryDate);
        //            }
        //            $this->em()->persist($price);
        //        }
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    private function updateContent(Product $product, $data)
    {


        $content = $product->getPost()->getContent();

        if (!isset($data['disabledDescription'])) {
            $content['description'] = $data['description'];
        }


        if (!isset($data['disabledBrief']) and array_key_exists("brief", $data)) {
            $content['brief'] = $data['brief'];
        }

        $product->getPost()->setContent($content);
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

}
