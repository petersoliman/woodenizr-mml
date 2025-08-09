<?php

namespace App\ShippingBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ShippingBundle\Entity\City;
use App\ShippingBundle\Form\CityType;
use App\ShippingBundle\Repository\CityRepository;
use App\UserBundle\Entity\User;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * City controller.
 *
 * @Route("city")
 */
class CityController extends AbstractController
{

    /**
     * @Route("/", name="city_index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        return $this->render('shipping/admin/city/index.html.twig');
    }

    /**
     * @Route("/new", name="city_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($city);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('city_index');
        }

        return $this->render('shipping/admin/city/new.html.twig', [
            'city' => $city,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="city_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, City $city): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($city);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('city_edit', ['id' => $city->getId()]);
        }

        return $this->render('shipping/admin/city/edit.html.twig', [
            'city' => $city,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="city_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, City $city): Response
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        if (count($city->getZones()) > 0) {
            $this->addFlash('error', 'Can not remove this city, it contain zones');

            return $this->redirectToRoute('city_index');
        }
        $city->setDeletedBy($userService->getUserName());
        $city->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($city);
        $this->em()->flush();

        return $this->redirectToRoute('city_index');
    }

    /**
     * Lists all city entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="city_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, CityRepository $cityRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $cityRepository->filter($search, true);

        $cities = $cityRepository->filter($search, false, $start, $length);

        return $this->render("shipping/admin/city/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "cities" => $cities,
        ]);
    }

}
