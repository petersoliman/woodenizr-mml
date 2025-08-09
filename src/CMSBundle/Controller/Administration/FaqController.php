<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Faq;
use App\CMSBundle\Form\FaqType;
use App\CMSBundle\Repository\FaqRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("faq")
 */
class FaqController extends AbstractController
{

    /**
     * @Route("/", name="faq_index", methods={"GET"})
     */
    public function index(): Response
    {

        return $this->render('cms/admin/faq/index.html.twig');
    }

    /**
     * Creates a new faq entity.
     *
     * @Route("/new", name="faq_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $faq = new Faq();
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($faq);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('faq_index');
        }

        return $this->render('cms/admin/faq/new.html.twig', [
            'faq' => $faq,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing faq entity.
     *
     * @Route("/{id}/edit", name="faq_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Faq $faq): Response
    {
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($faq);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('faq_edit', ['id' => $faq->getId()]);
        }

        return $this->render('cms/admin/faq/edit.html.twig', [
            'faq' => $faq,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a faq entity.
     *
     * @Route("/{id}", name="faq_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Faq $faq): Response
    {

        $this->em()->remove($faq);
        $this->em()->flush();

        return $this->redirectToRoute('faq_index');
    }

    /**
     * Lists all faq entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="faq_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, FaqRepository $faqRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];

        $count = $faqRepository->filter($search, true);
        $faqs = $faqRepository->filter($search, false, $start, $length);
        return $this->datatableConvertToJson($request, $faqs, $count);

        return $this->render("cms/admin/faq/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "faqs" => $faqs,
        ]);
    }

    private function datatableConvertToJson(Request $request, array $faqs, int $count): JsonResponse
    {
        $array = [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => [],
        ];


        $actionsMacro = function (Faq $faq) {
            $html = "<ul class='icons-list'>"
                . "<li class='dropdown'>"
                . "<a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='icon-menu9'></i></a>"
                . "<ul class='dropdown-menu dropdown-menu-right'>"
                . "<li><a href='" . $this->generateUrl("faq_edit",
                    ["id" => $faq->getId()]) . "'><i class='icon-pencil7'></i> Edit</a></li>";
            $html .= "<li><a href='#' class='delete-btn' data-toggle='modal' data-target='#modal_delete' data-delete='" . $this->generateUrl("faq_delete",
                    ["id" => $faq->getId()]) . "'><i class='icon-bin'></i> Remove</a></li>";
            $html .= "</ul>"
                . " </li>"
                . " </ul>";

            return $html;
        };
        foreach ($faqs as $faq) {
            $publish = $faq->isPublish() ? "<span class='label label-success'>Yes</span>" : "<span class='label label-danger'>No</span>";
            $array['data'][] = [
                $faq->getQuestion(),
                $faq->getAnswer(),
                $publish,
                $faq->getCreated()->format('d/m/Y h:i A'),
                $actionsMacro($faq),
            ];
        }


        return $this->json($array);
    }
}
