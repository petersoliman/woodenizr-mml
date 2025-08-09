<?php

namespace App\NewShippingBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\NewShippingBundle\Entity\Courier;
use App\NewShippingBundle\Form\CourierType;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("courier")
 */
class CourierController extends AbstractController
{

    /**
     * Lists all courier entities.
     *
     * @Route("/", name="courier_index", methods={"GET"})
     */
    public function index(): Response
    {

        return $this->render('newShipping/admin/courier/index.html.twig');
    }

    /**
     * Creates a new courier entity.
     *
     * @Route("/new", name="courier_new", methods={"GET", "POST"})
     */
    public function new(Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $courier = new Courier();
        $form = $this->createForm(CourierType::class, $courier);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($courier);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('courier_index');
        }
        return $this->render('newShipping/admin/courier/new.html.twig', array(
            'courier' => $courier,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing courier entity.
     *
     * @Route("/{id}/edit", name="courier_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Courier $courier): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(CourierType::class, $courier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($courier);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully updated');
            return $this->redirectToRoute('courier_edit', array('id' => $courier->getId()));
        }
        return $this->render('newShipping/admin/courier/edit.html.twig', array(
            'courier' => $courier,
            'form' => $form->createView(),
        ));
    }

    /**
     * Deletes a courier entity.
     *
     * @Route("/{id}", name="courier_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Courier $courier): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userName = $userService->getUserName();
        $courier->setDeletedBy($userName);
        $courier->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($courier);
        $this->em()->flush();

        return $this->redirectToRoute('courier_index');
    }

    /**
     * Lists all Courier entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="courier_datatable", methods={"GET"})
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

        $count = $this->em()->getRepository(Courier::class)->filter($search, TRUE);
        $couriers = $this->em()->getRepository(Courier::class)->filter($search, FALSE, $start, $length);

        return $this->render("newShipping/admin/courier/datatable.json.twig", array(
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "couriers" => $couriers,
            )
        );
    }

}
