<?php

namespace App\UserBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ECommerceBundle\Repository\OrderRepository;
use App\UserBundle\Entity\User;
use App\UserBundle\Form\Filter\UserFilterType;
use App\UserBundle\Form\UserType;
use App\UserBundle\Model\UserInterface;
use App\UserBundle\Repository\UserRepository;
use App\UserBundle\Security\CustomAuthenticator;
use App\UserBundle\Service\UserOperationService;
use JetBrains\PhpStorm\NoReturn;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Date;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{

    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(
        UserRepository $userRepository,
        Request        $request
    ): Response
    {
        $filterForm = $this->createForm(UserFilterType::class);
        $filterForm->handleRequest($request);
        $search = $this->collectSearchData($filterForm);

        return $this->render('user/admin/user/index.html.twig', [
            "search" => $search,
            "filter_form" => $filterForm->createView(),
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->add("enabled");
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($user);
            $this->em()->flush();

            $this->addFlash("success", "User created successfully");

            return $this->redirectToRoute("user_index");
        }

        return $this->render('user/admin/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->add("enabled");
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($user);
            $this->em()->flush();

            $this->addFlash("success", "User updated successfully");

            return $this->redirectToRoute("user_index");
        }

        return $this->render('user/admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Show user.
     *
     * @Route("/{id}/show", name="user_show", methods={"GET"})
     */
    public function show(User $user, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);
        $orders = $orderRepository->findBy(['user' => $user]);
        return $this->render('user/admin/user/show.html.twig', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="user_delete", methods={"GET", "POST"})
     */
    public function delete(User $user, UserService $userService, UserOperationService $userOperationService): Response
    {
        $userOperationService->deleteUser($user, $userService->getUserName());

        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute("user_index");
    }

    /**
     * @Route("/change-state/{id}", name="user_change_state", methods={"POST"})
     */
    public function changeState(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $user->setEnabled(!$user->isEnabled());
        $this->em()->persist($user);
        $this->em()->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Lists all Category entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="user_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, UserRepository $userRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");

        $filterForm = $this->createForm(UserFilterType::class);
        $filterForm->handleRequest($request);
        $search = $this->collectSearchData($filterForm);
        if (Validate::not_null($srch['value'])) {
            $search->string = $srch['value'];
        }
        $search->ordr = $ordr[0];

        $count = $userRepository->filter($search, true);
        $users = $userRepository->filter($search, false, $start, $length);

        return $this->render("user/admin/user/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "users" => $users,
        ]);
    }

    /**
     * @Route("/export-csv", name="user_export_csv", methods={"GET"})
     */
    #[NoReturn] public function exportCSV(Request $request, UserRepository $userRepository): Response
    {
        $userIds = $request->query->get("ids");
        if (!is_array($userIds) and Validate::not_null($userIds)) {
            $userIds = [$userIds];
        } else if (!is_array($userIds)) {
            $userIds = [];
        }


        $list[] = [
            "#",
            "Name",
            "Email",
            "Phone",
            "Last Login",
            "Cart items",
            "Success orders",
            "Created",
        ];
        $search = new \stdClass();
        $search->ids = $userIds;
        $users = $userRepository->filter($search);

        foreach ($users as $user) {
            $list = array_merge($list, $this->exportCsvRow($user));
        }
        $f = fopen('php://memory', 'w');
        // loop over the input array
        foreach ($list as $fields) {
            fputcsv($f, $fields, ",");
        }
        fseek($f, 0);

        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="user-' . date("Y-m-d") . '.csv";');

        fpassthru($f);

        exit;
    }

    /**
     * Deletes a Merchant entity.
     *
     * @Route("/mass-delete", name="user_mass_delete", methods={"POST"})
     */
    public function massDelete(
        Request              $request,
        UserRepository       $userRepository,
        UserService          $userService,
        UserOperationService $userOperationService
    ): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $userIds = $request->request->get('userIds');
        if (!is_array($userIds)) {
            return $this->json(['error' => 1, "message" => "Please enter select"]);
        }

        foreach ($userIds as $userId) {
            $user = $userRepository->find($userId);
            if ($user == null) {
                continue;
            }
            $userOperationService->deleteUser($user, $userService->getUserName());
        }

        return $this->redirect($request->headers->get('referer'));
    }


    /**
     * @Route("/login-as/{id}", requirements={"id" = "\d+"}, name="user_login_as_user", methods={"GET"})
     */
    public function loginAsUser(
        Request                    $request,
        User                       $user,
        UserAuthenticatorInterface $userAuthenticator,
        CustomAuthenticator        $authenticator
    ): Response
    {

        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        if (!$user->isEnabled()) {
            $this->addFlash('error', "this user is blocked, so you can't login with this account");

            return $this->redirect($request->headers->get('referer'));
        }
        $session = $request->getSession();
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $currentUserId = $currentUser->getId();
            $session->set("lastLoginAsAdminId", $currentUserId);
        }

        $userAuthenticator->authenticateUser($user, $authenticator, $request);

        if ($this->isGranted(User::ROLE_SUPER_ADMIN)) {
            return $this->redirectToRoute('dashboard');
        } elseif ($this->isGranted(User::ROLE_ADMIN)) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->redirectToRoute('fe_home');
    }

    private function collectSearchData(FormInterface $form): \stdClass
    {
        $search = new \stdClass;
        $search->deleted = 0;
        $search->string = $form->get("str")->getData();
        $search->regDateFrom = ($form->get("createdFrom")->getData()) ? $form->get("createdFrom")->getData()->format(Date::DATE_FORMAT3) : null;
        $search->regDateTo = ($form->get("createdTo")->getData()) ? $form->get("createdTo")->getData()->format(Date::DATE_FORMAT3) : null;
        $search->enabled = $form->get("enabled")->getData();
        $search->role = User::ROLE_DEFAULT;

        return $search;
    }


    private function exportCsvRow(User $user): array
    {
        $return [] = [
            $user->getId(),
            $user->getFullName(),
            $user->getEmail(),
            $user->getPhone(),
            ($user->getLastLogin()) ? $user->getLastLogin()->format(Date::DATE_FORMAT6) : "--",
            $user->getCartItemsNo(),
            $user->getSuccessOrderNo(),
            $user->getCreated()->format(Date::DATE_FORMAT6),
        ];
        return $return;
    }
}