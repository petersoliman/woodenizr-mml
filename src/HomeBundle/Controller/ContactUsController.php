<?php

namespace App\HomeBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\CMSBundle\Service\SiteSettingService;
use App\HomeBundle\Form\ContactUsType;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\ServiceBundle\Service\SendEmailLaterService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

/**
 * contactus controller.
 *
 * @Route("contact-us")
 */
class ContactUsController extends AbstractController
{

    /**
     * @Route("", name="fe_contact_index", methods={"GET", "POST"})
     */
    public function index(
        Request               $request,
        SiteSettingService    $siteSettingService,
        SendEmailLaterService $mailer,
        SeoPageRepository     $seoPageRepository
    ): Response
    {
        // Create the form according to the FormType created previously.
        // And give the proper parameters
        $form = $this->createForm(ContactUsType::class, null, [
            'method' => 'POST',
        ]);

        // Refill the fields in case the form is not valid.
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Send mail
            $data = $form->getData();
            $this->sendEmail($siteSettingService, $mailer, $data);

            return $this->redirectToRoute('fe_contact_thanks');
        }

        return $this->render('home/contact/index.html.twig', [
            'seoPage' => $seoPageRepository->findOneByType("contact-us"),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/thanks", name="fe_contact_thanks", methods={"GET"})
     */
    public function thanks(
        SeoPageRepository $seoPageRepository
    ): Response
    {
        return $this->render('home/contact/thanks.html.twig', [
            'seoPage' => $seoPageRepository->findOneByType("contact-us"),
        ]);
    }

    private function sendEmail(SiteSettingService $siteSettingService, SendEmailLaterService $mailer, array $data): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($siteSettingService->getByConstantName('email-from'), $siteSettingService->getByConstantName('website-title')))
            ->to(new Address($siteSettingService->getByConstantName('admin-email')))
            ->subject($siteSettingService->getByConstantName('website-title') . ' - contact us from ' . $data['name'])
            ->htmlTemplate('home/contact/adminEmail.html.twig')
            ->context([
                'data' => $data,
            ]);
        $mailer->send($email);
    }

}
