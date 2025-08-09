<?php

namespace App\HomeBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Service\BannerService;
use App\CMSBundle\Service\SiteSettingService;
use App\HomeBundle\Form\CareerType;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\ServiceBundle\Service\SendEmailNowService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Career controller.
 *
 * @Route("career")
 */
class CareerController extends AbstractController
{

    /**
     * career form.
     *
     * @Route("", name="fe_career", methods={"GET", "POST"})
     */
    public function career(
        Request             $request,
        BannerService       $bannerService,
        SiteSettingService  $siteSettingService,
        SeoPageRepository   $seoPageRepository,
        SendEmailNowService $mailer
    ): Response
    {

        // Create the form according to the FormType created previously.
        // And give the proper parameters
        $form = $this->createForm(CareerType::class, null, [
            'method' => 'POST',
        ]);

        // Refill the fields in case the form is not valid.
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Send mail
            $data = $form->getData();
            $this->sendEmail($siteSettingService, $mailer, $data);


            $this->addFlash('success', 'Your application is successfully sent');

            return $this->redirectToRoute('fe_career');
        }

        $banner = $bannerService->getOneBanner(8);

        return $this->render('home/career/index.html.twig', [
            'seoPage' => $seoPageRepository->find(6),
            'banner' => $banner,
            'career_form' => $form->createView(),
        ]);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendEmail(SiteSettingService $siteSettingService, SendEmailNowService $mailer, array $data)
    {
        $email = (new TemplatedEmail())
            ->from(new Address($siteSettingService->getByConstantName('email-from'), $siteSettingService->getByConstantName('website-title')))
            ->to(new Address($siteSettingService->getByConstantName('admin-email')))
            ->subject($siteSettingService->getByConstantName('website-title') . ' | new resume from ' . $data['name'])
            ->htmlTemplate('home/career/adminEmail.html.twig')
            ->context([
                'data' => $data,
            ]);
        if ($data['resume'] != null) {
            $email->attachFromPath($data['resume']->getPathname(), $data['resume']->getClientOriginalName(),
                $data['resume']->getMimeType());
        }

        $mailer->send($email);
    }

}
