<?php

namespace App\OnlinePaymentBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\OnlinePaymentBundle\Form\PaymentMethodType;
use PN\MediaBundle\Entity\Image;
use PN\MediaBundle\Service\UploadImageService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PaymentMethod controller.
 *
 * @Route("/payment_method")
 */
class PaymentMethodController extends AbstractController
{

    /**
     * Lists all PaymentMethod entities.
     *
     * @Route("/", name="payment_method_index", methods={"GET"})
     */
    public function indexAction(): Response
    {
        return $this->render('onlinePayment/admin/paymentMethod/index.html.twig');
    }

    /**
     * Displays a form to edit an existing PaymentMethod entity.
     *
     * @Route("/{id}/edit", name="payment_method_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, UploadImageService $uploadImageService, PaymentMethod $paymentMethod): Response
    {
        $form = $this->createForm(PaymentMethodType::class, $paymentMethod);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($paymentMethod);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $paymentMethod);
            if ($uploadImage === false) {
                return $this->redirectToRoute('payment_method_edit', ['id' => $paymentMethod->getId()]);
            }

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('payment_method_index');
        }

        return $this->render('onlinePayment/admin/paymentMethod/edit.html.twig', [
            'paymentMethod' => $paymentMethod,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lists all PaymentMethod entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="payment_method_datatable", methods={"GET"})
     */
    public function dataTableAction(Request $request): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $this->em()->getRepository(PaymentMethod::class)->filter($search, true);
        $paymentMethods = $this->em()->getRepository(PaymentMethod::class)->filter($search, false, $start, $length);

        return $this->render("onlinePayment/admin/paymentMethod/datatable.json.twig", [
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "paymentMethods" => $paymentMethods,
            ]
        );
    }

    private function uploadImage(
        Request            $request,
        UploadImageService $uploadImageService,
        FormInterface      $form,
        PaymentMethod      $entity
    ): bool|string|Image
    {
        $file = $form->get("image")->get("file")->getData();
        if (!$file instanceof UploadedFile) {
            return true;
        }

        return $uploadImageService->uploadSingleImage($entity, $file, 103, $request);
    }
}
