<?php

namespace App\NewShippingBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\NewShippingBundle\Entity\Zone;
use App\NewShippingBundle\Form\ZoneType;
use App\VendorBundle\Entity\StoreAddress;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("zone")
 */
class ZoneController extends AbstractController
{

    /**
     * Lists all zone entities.
     *
     * @Route("", requirements={"id": "\d+"}, defaults={"id": null}, name="zone_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('newShipping/admin/zone/index.html.twig');
    }

    /**
     * Creates a new zone entity.
     *
     * @Route("/new", requirements={"id": "\d+"}, defaults={"id": null}, name="zone_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $zone = new Zone();
        $form = $this->createForm(ZoneType::class, $zone);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($zone);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('zone_index');
        }


        return $this->render('newShipping/admin/zone/new.html.twig', array(
            'zone' => $zone,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing zone entity.
     *
     * @Route("/{id}/edit", name="zone_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Zone $zone): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ZoneType::class, $zone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($zone);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('zone_edit', array('id' => $zone->getId()));
        }


        return $this->render('newShipping/admin/zone/edit.html.twig', array(
            'zone' => $zone,
            'form' => $form->createView(),
        ));
    }

    /**
     * Deletes a zone entity.
     *
     * @Route("/{id}", name="zone_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Zone $zone): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $storeAddress = $this->em()->getRepository(StoreAddress::class)->findOneBy([
            "zone" => $zone,
            "deleted" => null,
        ]);
        if ($storeAddress) {
            $this->addFlash("error", "You can't deleted, because this item related with store address");

            return $this->redirectToRoute('zone_index');
        }


        $userName = $userService->getUserName();
        $zone->setDeletedBy($userName);
        $zone->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($zone);
        $this->em()->flush();

        return $this->redirectToRoute('zone_index');
    }

    /**
     * Lists all Zone entities.
     *
     * @Route("/data/table", requirements={"id": "\d+"}, defaults={"id": null, "_format": "json"}, name="zone_datatable", methods={"GET"})
     */
    public function dataTable(Request $request): Response
    {

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $this->em()->getRepository(Zone::class)->filter($search, true);
        $zones = $this->em()->getRepository(Zone::class)->filter($search, false, $start, $length);
        foreach ($zones as $zone) {
            $searchChildren = new \stdClass();
            $searchChildren->deleted = 0;
            $searchChildren->parent = $zone->getId();
            $zone->countChildren = $this->em()->getRepository(Zone::class)->filter($searchChildren, true);
        }

        return $this->render("newShipping/admin/zone/datatable.json.twig", array(
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "zones" => $zones,
            )
        );
    }


    /**
     * @Route("/get-ready-for-shipping-list-ajax", name="zone_get_by_site_country_ajax", methods={"POST"})
     */
    public function getShippingAddressAjax(Request $request): JsonResponse
    {

        $siteCountryId = $request->request->get('siteCountry');
        if (!Validate::not_null($siteCountryId)) {
            $this->json([]);
        }

        $zones = $this->em()->getRepository(Zone::class)->getZonesReadyToShipping();

        $returnArray = [];
        foreach ($zones as $zone) {
            $returnArray[] = ['id' => $zone->getId(), 'text' => $zone->getTitle()];
        }

        return $this->json($returnArray);
    }

}
