<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Service;
use App\CMSBundle\Form\ServiceType;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Service controller.
 *
 * @Route("service")
 */
class ServiceController extends AbstractController
{

    /**
     * Lists all service entities.
     *
     * @Route("/", name="service_index",methods={"GET"})
     */
    public function indexAction()
    {

        return $this->render('cms/admin/service/index.html.twig');
    }

    /**
     * Creates a new service entity.
     *
     * @Route("/new", name="service_new",methods={"GET","POST"})
     */
    public function newAction(Request $request, UserService $userService)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $service->setCreator($userName);
            $service->setModifiedBy($userName);

            $this->em()->persist($service);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('post_set_images', ['id' => $service->getPost()->getId()]);
        }

        return $this->render('cms/admin/service/new.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing service entity.
     *
     * @Route("/{id}/edit", name="service_edit",methods={"GET", "POST"})
     */
    public function editAction(Request $request, UserService $userService, Service $service)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $service->setModifiedBy($userName);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('service_edit', ['id' => $service->getId()]);
        }

        return $this->render('cms/admin/service/edit.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a service entity.
     *
     * @Route("/{id}", name="service_delete",methods={"DELETE"})
     */
    public function deleteAction(Request $request, UserService $userService, Service $service)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $userName = $userService->getUserName();
        $service->setDeletedBy($userName);
        $service->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($service);
        $this->em()->flush();

        return $this->redirectToRoute('service_index');
    }

    /**
     * Lists all Service entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="service_datatable",methods={"GET"})
     */
    public function dataTableAction(Request $request)
    {


        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->deleted = 0;

        $count = $this->em()->getRepository('CMSBundle:Service')->filter($search, true);
        $services = $this->em()->getRepository('CMSBundle:Service')->filter($search, false, $start, $length);

        return $this->render("cms/admin/service/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "services" => $services,
        ]);
    }

}
