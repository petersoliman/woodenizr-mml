<?php

namespace App\NewShippingBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Form\ShippingZoneType;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("shipping-zone")
 */
class ShippingZoneController extends AbstractController
{

    /**
     * Lists all shippingZone entities.
     *
     * @Route("/", name="shipping_zone_index", methods={"GET"})
     */
    public function index(): Response
    {

        return $this->render('newShipping/admin/shippingZone/index.html.twig');
    }

    /**
     * Creates a new shippingZone entity.
     *
     * @Route("/new", name="shipping_zone_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $shippingZone = new ShippingZone();
        $form = $this->createForm(ShippingZoneType::class, $shippingZone);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($shippingZone);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('shipping_zone_edit', ["id" => $shippingZone->getId()]);
        }

        return $this->render('newShipping/admin/shippingZone/new.html.twig', array(
            'shippingZone' => $shippingZone,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing shippingZone entity.
     *
     * @Route("/{id}/edit", name="shipping_zone_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, ShippingZone $shippingZone): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ShippingZoneType::class, $shippingZone);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($shippingZone);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('shipping_zone_edit', array('id' => $shippingZone->getId()));
        }

        return $this->render('newShipping/admin/shippingZone/edit.html.twig', array(
            'shippingZone' => $shippingZone,
            'form' => $form->createView(),
        ));
    }

    /**
     * Deletes a shippingZone entity.
     *
     * @Route("/{id}", name="shipping_zone_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, ShippingZone $shippingZone): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $checkIfUsed = $this->em()->getRepository(ShippingZonePrice::class)->findOneBy(["sourceShippingZone" => $shippingZone]);
        if ($checkIfUsed) {
            $this->addFlash("error",
                "You can't delete this shipping zone, because this one is using in shipping price");

            return $this->redirectToRoute('shipping_zone_index');
        }
        $checkIfUsed = $this->em()->getRepository(ShippingZonePrice::class)->findOneBy(["targetShippingZone" => $shippingZone]);
        if ($checkIfUsed) {
            $this->addFlash("error",
                "You can't delete this shipping zone, because this one is using in shipping price");

            return $this->redirectToRoute('shipping_zone_index');
        }

        $userName = $userService->getUserName();
        $shippingZone->setDeletedBy($userName);
        $shippingZone->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->remove($shippingZone);
        $this->em()->flush();

        return $this->redirectToRoute('shipping_zone_index');
    }

    /**
     * Lists all ShippingZone entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="shipping_zone_datatable", methods={"GET"})
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

        $count = $this->em()->getRepository(ShippingZone::class)->filter($search, true);
        $shippingZones = $this->em()->getRepository(ShippingZone::class)->filter($search, false, $start, $length);

        return $this->render("newShipping/admin/shippingZone/datatable.json.twig", array(
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "shippingZones" => $shippingZones,
            )
        );
    }
}
