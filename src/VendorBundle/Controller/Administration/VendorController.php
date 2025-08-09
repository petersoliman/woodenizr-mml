<?php

namespace App\VendorBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\VendorBundle\Entity\Vendor;
use App\VendorBundle\Form\VendorType;
use App\VendorBundle\Repository\VendorRepository;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * User controller.
 *
 * @Route("/")
 */
class VendorController extends AbstractController
{

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("", name="vendor_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('vendor/admin/vendor/index.html.twig');
    }

    /**
     * Creates a new user entity.
     *
     * @Route("/new", name="vendor_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UploadImageService $uploadImageService): RedirectResponse|Response
    {

        $vendor = new Vendor();
        $form = $this->createForm(VendorType::class, $vendor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($vendor);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $vendor);
            if ($uploadImage != false) {
                $this->addFlash('success', 'Successfully saved');

                return $this->redirectToRoute('vendor_index');
            }

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('vendor_edit', ["id" => $vendor->getId()]);
        }

        return $this->render('vendor/admin/vendor/new.html.twig', array(
            'vendor' => $vendor,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="vendor_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UploadImageService $uploadImageService, Vendor $vendor): RedirectResponse|Response
    {

        $form = $this->createForm(VendorType::class, $vendor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($vendor);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $vendor);
            if ($uploadImage != false) {
                $this->addFlash('success', 'Successfully saved');

                return $this->redirectToRoute('vendor_index');
            }

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('vendor_edit', ['id' => $vendor->getId()]);
        }

        return $this->render('vendor/admin/vendor/edit.html.twig', array(
            'vendor' => $vendor,
            'form' => $form->createView(),
        ));
    }


    private function uploadImage(Request $request, UploadImageService $uploadImageService, FormInterface $form, Vendor $entity): bool
    {
        if ($form->has("image")) {
            $file = $form->get("image")->get("file")->getData();
            if ($file == null) {
                return true;
            }

            $uploadImageService->uploadSingleImage($entity, $file, 104, $request);
        }

        return true;
    }

    /**
     * Deletes a storeAddress entity.
     *
     * @Route("/{id}", name="vendor_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, UserService $userService, Vendor $vendor): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $userName = $userService->getUserName();
        $vendor->setDeletedBy($userName);
        $vendor->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($vendor);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully Deleted');

        return $this->redirectToRoute('vendor_index', ["id" => $vendor->getId()]);

    }
    /**
     * Lists all Category entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="vendor_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, VendorRepository $vendorRepository): Response
    {

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $search = new \stdClass;
        $search->deleted = 0;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];

        $count = $vendorRepository->filter($search, true);
        $vendors = $vendorRepository->filter($search, false, $start, $length);

        return $this->render("vendor/admin/vendor/datatable.json.twig", array(
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "vendors" => $vendors,
            )
        );
    }

    /**
     * @Route("/search/select2-ajax", name="vendor_select2_ajax", methods={"GET"})
     */
    public function vendorSearchSelect2(Request $request, VendorRepository $vendorRepository): JsonResponse
    {

        $page = ($request->query->has('page')) ? $request->get('page') : 1;

        $search = new \stdClass();
        $search->string = $request->get('q');
        $search->deleted = 0;
//        $search->publish = 1;

        if (!Validate::not_null($search->string)) {
            return $this->json([]);
        }


        $count = $vendorRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 5);
        $vendors = $vendorRepository->filter($search, false, $paginator->getLimitStart(),
            $paginator->getPageLimit());

        $paginationFlag = false;
        if (isset($paginator->getPagination()['last']) and $paginator->getPagination()['last'] != $page) {
            $paginationFlag = true;
        }

        $returnArray = [
            'results' => [],
            'pagination' => $paginationFlag,
        ];

        foreach ($vendors as $vendor) {
            $returnArray['results'][] = [
                'id' => $vendor->getId(),
                'text' => $vendor->getTitle(),
            ];
        }

        return $this->json($returnArray);
    }

    /**
     * Displays a form to edit an existing Person entity.
     *
     * @Route("/vendor-store-address-ajaxall", name="vendor_store_address_select_ajax", methods={"GET"})
     */
    public function storeAddressSelect2(Request $request, VendorRepository $vendorRepository): JsonResponse
    {
        $vendorId = ($request->query->has('vendorId')) ? $request->get('vendorId') : null;

        $returnArray = [
            'results' => [],
            'pagination' => false,
        ];
        if ($vendorId > 0) {
            $vendor = $vendorRepository->find($vendorId);
            foreach ($vendor->getStoreAddresses() as $storeAddress) {
                $returnArray['results'][] = ['id' => $storeAddress->getId(), 'text' => $storeAddress->__toString()];
            }

        }

        return $this->json($returnArray);
    }


}
