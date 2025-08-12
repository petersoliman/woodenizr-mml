<?php

namespace App\UserBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\ProductPriceTypeEnum;
use App\BaseBundle\SystemConfiguration;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Repository\OrderRepository;
use App\ProductBundle\Entity\ProductVariantOption;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\ProductSearchService;
use App\ShippingBundle\Service\ShippingService;
use App\UserBundle\Entity\User;
use App\UserBundle\Form\ChangePasswordFormType;
use App\UserBundle\Form\ProfileType;
use App\UserBundle\Model\UserInterface;
use PN\ServiceBundle\Lib\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Faq controller.
 *
 * @Route("profile")
 */
class ProfileController extends AbstractController
{

    /**
     * @Route("/edit", name="fe_profile_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, EventDispatcherInterface $dispatcher): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $this->em()->persist($user);
                $this->em()->flush();

                $this->addFlash("success", "Saved Successfully");

                return $this->redirectToRoute('fe_profile_edit');
            }
            $this->em()->refresh($user);
        }
        return $this->render('user/frontEnd/profile/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/wishlist/{page}/{sort}", requirements={"page" = "\d+"}, name="fe_profile_wishlist", methods={"GET"})
     */
    public function wishlistAction(Request $request, TranslatorInterface $translator, int $page = 1, int $sort = 1): Response
    {
        if (!$this->getUser() instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('user/frontEnd/profile/wishlist.html.twig', [
            "sortBy" => $this->getWishlistSortBy($request, $translator),
        ]);
    }

    private function getWishlistSortBy(Request $request, TranslatorInterface $translator): array
    {
        $routeName = $request->attributes->get('_route');
        $generateUrl = function (int $sortNumber) use ($request, $routeName) {
            $routeParameters = $request->attributes->get('_route_params');
            $queryParameters = $request->query->all();

            $params = array_merge($routeParameters, $queryParameters, ["page" => 1, "sort" => $sortNumber]);

            return $this->generateUrl($routeName, $params);
        };

        $getCurrentSortTitle = function (int $currentSortNumber, array $sortTypes) {
            foreach ($sortTypes as $sortType) {
                if ($sortType['sortNumber'] == $currentSortNumber) {
                    return $sortType['title'];
                }
            }

            return $sortTypes[0]['title'];
        };

        $sortTypes = [
            [
                "sortNumber" => 1,
                "title" => $translator->trans("product_filter_sort_recommended_txt"),
            ],
            [
                "sortNumber" => 2,
                "title" => $translator->trans("product_filter_sort_recently_added_txt"),
            ],
            [
                "sortNumber" => 3,
                "title" => $translator->trans("product_filter_sort_price_low_to_high_txt"),
            ],
            [
                "sortNumber" => 4,
                "title" => $translator->trans("product_filter_sort_price_high_to_low_txt"),
            ],
        ];


        $currentSortNumber = $request->get("sort");

        $sorts = [
            "currentSortNumber" => $currentSortNumber,
            "currentSortTitle" => $getCurrentSortTitle($currentSortNumber, $sortTypes),
            "types" => [],
        ];
        foreach ($sortTypes as $sortType) {
            $sortNumber = $sortType['sortNumber'];
            $title = $sortType['title'];

            $sorts["types"][] = [
                "sortNumber" => $sortNumber,
                "title" => $title,
                "url" => $generateUrl($sortNumber),
                "isSelected" => $currentSortNumber == $sortNumber,
            ];
        }

        return $sorts;
    }

    /**
     * @Route("/wishlist-ajax/{page}/{sort}", requirements={"page" = "\d+"}, name="fe_profile_wishlist_api", methods={"GET"})
     */
    public function wishlistAPIAction(
        Request                 $request,
        TranslatorInterface     $translator,
        ProductSearchService    $productSearchService,
        ProductSearchRepository $productSearchRepository,
        int                     $page = 1,
        int                     $sort = 1
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => $translator->trans("please_login_first_msg")]);
        }
        $order = match ($sort) {
            2 => ["column" => 1, "dir" => "DESC"],
            3 => ["column" => 2, "dir" => "ASC"],
            4 => ["column" => 2, "dir" => "DESC"],
            default => ["column" => 0, "dir" => "DESC"],
        };
        $search = new \stdClass();
        $search->ordr = $order;
        $search->favoriteUserId = $user->getId();
        $search->currentUserId = $user->getId();
        $count = $productSearchRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 24);
        $favoriteProducts = $productSearchRepository->filter($search, false, $paginator->getLimitStart(), $paginator->getPageLimit());

        $productObjects = [];
        foreach ($favoriteProducts as $product) {
            $productObjects[] = $productSearchService->convertEntityToObject($product);
        }

        $paginationRendered = $this->renderView("fe/_pagination.html.twig", [
            "paginator" => $paginator->getPagination(),
            "queryParams" => $request->request->all(),
        ]);

        return $this->json([
            "error" => false,
            "message" => null,
            "noOfProducts" => $count,
            "products" => $productObjects,
            "paginationHTML" => $paginationRendered,
        ]);
    }

    /**
     * @Route("/address-book", name="fe_profile_address_book", methods={"GET", "POST"})
     */
    public function addressBookAction(Request $request, ShippingService $shippingService): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('user/frontEnd/profile/address_book.html.twig', [
            'zones' => $shippingService->getZonesReadyToShipping(),
        ]);
    }

    /**
     * @Route("/my-orders/{page}", requirements={"page" = "\d+"}, name="fe_profile_my_orders", methods={"GET", "POST"})
     */
    public function myOrdersAction(Request $request, OrderRepository $orderRepository, int $page = 1): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        $search = new \stdClass;
        $search->user = $user->getId();
        $search->ordr = ["column" => 0, "dir" => "DESC"];
        $count = $orderRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 10);
        $orders = $orderRepository->filter($search, false, $paginator->getLimitStart(), $paginator->getPageLimit());


        return $this->render('user/frontEnd/profile/my_orders.html.twig', [
            'orders' => $orders,
            'paginator' => $paginator->getPagination(),
        ]);
    }

    /**
     * @Route("/my-orders/show/{uuid}", requirements={"page" = "\d+"}, name="fe_profile_my_order_show", methods={"GET", "POST"})
     */
    public function orderShowAction(Request $request, Order $order): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }
        if ($order->getUser() != $user) {
            throw $this->createAccessDeniedException();
        }


        if (SystemConfiguration::PRODUCT_PRICE_TYPE == ProductPriceTypeEnum::VARIANTS) {

            foreach ($order->getOrderHasProductPrices() as $orderHasProductPrice) {
                $variants = [];
                $productPrice = $orderHasProductPrice->getProductPrice();
                if ($productPrice->getVariantOptionIds() != null) {

                    $search = new \stdClass();
                    $search->deleted = 0;
                    $search->ids = explode("-", $productPrice->getVariantOptionIds());
                    $variantOptions = $this->em()->getRepository(ProductVariantOption::class)->filter($search);

                    foreach ($variantOptions as $option) {
                        $variants[] = $option->getVariant()->getTitle() . ": " . $option->getTitle();
                    }
                }
                $productPrice->virtualVariants = $variants;
            }
        }
        return $this->render('user/frontEnd/profile/my_order_show.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * @Route("/change-password", requirements={"page" = "\d+"}, name="fe_profile_change_password", methods={"GET", "POST"})
     */
    public function changePasswordAction(
        Request                     $request,
        UserPasswordHasherInterface $userPasswordHasher,
        TranslatorInterface         $translator
    ): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->em()->persist($user);
            $this->em()->flush();

            $this->addFlash("success", $translator->trans("saved_successfully_txt"));

            return $this->redirectToRoute('fe_profile_change_password');
        }

        return $this->render('user/frontEnd/profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}

