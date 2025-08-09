<?php

namespace App\NewShippingBundle\Controller\Administration;

use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Form\ShippingTimeType;
use App\UserBundle\Entity\User;
use App\BaseBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ShippingTime controller.
 *
 * @Route("/shipping-time")
 */
class ShippingTimeController extends AbstractController
{

    /**
     * Lists all ShippingTime entities.
     *
     * @Route("/", name="shipping_time_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('newShipping/admin/shippingTime/index.html.twig');
    }


    /**
     * Creates a new ShippingTime entity.
     *
     * @Route("/new", name="shipping_time_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $shippingTime = new ShippingTime();
        $form = $this->createForm(ShippingTimeType::class, $shippingTime);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
                        $this->em()->persist($shippingTime);
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('shipping_time_index');

        }


        return $this->render('newShipping/admin/shippingTime/new.html.twig', [
            'shippingTime' => $shippingTime,
            'form' => $form->createView(),
        ]);
    }


    /**
     * Displays a form to edit an existing ShippingTime entity.
     *
     * @Route("/{id}/edit", name="shipping_time_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, ShippingTime $shippingTime): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ACCOUNTING);

        $form = $this->createForm(ShippingTimeType::class, $shippingTime);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->em()->persist($shippingTime);
            $this->em()->flush();
            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('shipping_time_edit', array('id' => $shippingTime->getId()));
        }


        return $this->render('newShipping/admin/shippingTime/edit.html.twig', [
            'shippingTime' => $shippingTime,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a ShippingTime entity.
     *
     * @Route("/{id}", name="shipping_time_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ShippingTime $shippingTime): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        
        $shippingTime->setDeleted(true);
        $this->em()->persist($shippingTime);
        $this->em()->flush();

        return $this->redirectToRoute('shipping_time_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="shipping_time_datatable", methods={"GET"})
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

        $count = $this->em()->getRepository(ShippingTime::class)->filter($search, true);
        $shippingTimes = $this->em()->getRepository(ShippingTime::class)->filter($search, false, $start, $length);

        return $this->render("newShipping/admin/shippingTime/datatable.json.twig", array(
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "shippingTimes" => $shippingTimes,
            )
        );
    }

}
