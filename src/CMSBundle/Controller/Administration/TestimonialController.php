<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Testimonial;
use App\CMSBundle\Form\TestimonialType;
use App\CMSBundle\Repository\TestimonialRepository;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Testimonial controller.
 *
 * @Route("testimonial")
 */
class TestimonialController extends AbstractController
{

    /**
     * Lists all Testimonial entities.
     *
     * @Route("/", name="testimonial_index",methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('cms/admin/testimonial/index.html.twig');
    }

    /**
     * Creates a new testimonial entity.
     *
     * @Route("/new", name="testimonial_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $testimonial = new Testimonial();
        $form = $this->createForm(TestimonialType::class, $testimonial);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($testimonial);
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('testimonial_index');
        }

        return $this->render('cms/admin/testimonial/new.html.twig', [
            'testimonial' => $testimonial,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing testimonial entity.
     *
     * @Route("/{id}/edit", name="testimonial_edit",methods={"GET", "POST"})
     */
    public function edit(Request $request, Testimonial $testimonial): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(TestimonialType::class, $testimonial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('testimonial_edit', [
                'id' => $testimonial->getId(),
            ]);
        }

        return $this->render('cms/admin/testimonial/edit.html.twig', [
            'testimonial' => $testimonial,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a testimonial entity.
     *
     * @Route("/{id}", name="testimonial_delete",methods={"DELETE"})
     */
    public function delete(UserService $userService, Testimonial $testimonial): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $userName = $userService->getUserName();
        $testimonial->setDeletedBy($userName);
        $testimonial->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($testimonial);
        $this->em()->flush();
        $this->addFlash('success', 'Deleted Successfully');

        return $this->redirectToRoute('testimonial_index');
    }

    /**
     * Lists all Testimonial entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="testimonial_datatable",methods={"GET"})
     */
    public function dataTable(Request $request, TestimonialRepository $testimonialRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->deleted = 0;

        $count = $testimonialRepository->filter($search, true);
        $testimonials = $testimonialRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/testimonial/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "testimonials" => $testimonials,
        ]);
    }

}
