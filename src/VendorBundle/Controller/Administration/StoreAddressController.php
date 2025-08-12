<?php

namespace App\VendorBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\Product;
use App\VendorBundle\Entity\Vendor;
use App\VendorBundle\Entity\StoreAddress;
use App\VendorBundle\Form\StoreAddressType;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("store-address")
 */
class StoreAddressController extends AbstractController
{

    /**
     * Lists all storeAddress entities.
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="store_address_index", methods={"GET"})
     */
    public function indexAction(Request $request, Vendor $vendor): Response
    {

        return $this->render('vendor/admin/storeAddress/index.html.twig', [
            'vendor' => $vendor,
        ]);
    }

    /**
     * Creates a new storeAddress entity.
     *
     * @Route("/new/{id}", requirements={"id" = "\d+"}, name="store_address_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, Vendor $vendor = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $storeAddress = new StoreAddress();
        $storeAddress->setVendor($vendor);
        $form = $this->createForm(StoreAddressType::class, $storeAddress);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($storeAddress);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('store_address_index', ["id" => $storeAddress->getVendor()->getId()]);
        }

        return $this->render('vendor/admin/storeAddress/new.html.twig', [
            'storeAddress' => $storeAddress,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing storeAddress entity.
     *
     * @Route("/{id}/edit", name="store_address_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, StoreAddress $storeAddress): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(StoreAddressType::class, $storeAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($storeAddress);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('store_address_index', ["id" => $storeAddress->getVendor()->getId()]);
        }

        return $this->render('vendor/admin/storeAddress/edit.html.twig', [
            'storeAddress' => $storeAddress,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a storeAddress entity.
     *
     * @Route("/{id}", name="store_address_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, UserService $userService, StoreAddress $storeAddress): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $userName = $userService->getUserName();
        $storeAddress->setDeletedBy($userName);
        $storeAddress->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($storeAddress);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully Deleted');

        return $this->redirectToRoute('store_address_index', ["id" => $storeAddress->getVendor()->getId()]);

    }

    /**
     * Lists all StoreAddress entities.
     *
     * @Route("/data/table/{id}", defaults={"id" = "\d+", "_format": "json"}, name="store_address_datatable", methods={"GET"})
     */
    public function dataTableAction(Request $request, Vendor $vendor = null): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;
        if ($vendor instanceof Vendor) {
            $search->vendor = $vendor->getId();
        }

        $_route = $request->get('_route');


        $count = $this->em()->getRepository(StoreAddress::class)->filter($search, true);
        $storeAddresses = $this->em()->getRepository(StoreAddress::class)->filter($search, false, $start, $length);

        return $this->render("vendor/admin/storeAddress/datatable.json.twig", [
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "storeAddresses" => $storeAddresses,
            ]
        );
    }

}
