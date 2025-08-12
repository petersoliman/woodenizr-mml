<?php

namespace App\ECommerceBundle\Service;

use App\BaseBundle\SystemConfiguration;
use App\CMSBundle\Service\SiteSettingService;
use App\ECommerceBundle\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\SendEmailLaterService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class OrderEmailService
{
    private EntityManagerInterface $em;
    private SendEmailLaterService $mailer;
    private SiteSettingService $siteSettingService;

    public function __construct(EntityManagerInterface $em, SendEmailLaterService $mailer, SiteSettingService $siteSettingService)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->siteSettingService = $siteSettingService;
    }

    public function sendEmailWhenChangeStatus(Order $order, $statusType, $newStatus): void
    {

        $email = (new TemplatedEmail())
            ->subject($this->siteSettingService->getByConstantName('website-title') . ' | Order #' . $order->getId() . " Change order " . $statusType . " to " . $newStatus)
            ->from(new Address($this->siteSettingService->getByConstantName("email-from"), $this->siteSettingService->getByConstantName('website-title')))
            ->to(new Address($order->getBuyerEmail()))
            ->htmlTemplate('eCommerce/admin/order/updateStatusEmail.html.twig')
            ->context([
                'order' => $order,
                "statusType" => $statusType,
                "newStatus" => $newStatus,
            ]);

        $this->mailer->send($email);


    }

    public function sendConfirmationEmailAfterCreateOrderToUser(Order $order): void
    {
        if ($order->isSentConfirmationEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->subject($this->siteSettingService->getByConstantName('website-title') . " | Order #" . $order->getId() . " Confirmed")
            ->from(new Address($this->siteSettingService->getByConstantName("email-from"), $this->siteSettingService->getByConstantName('website-title')))
            ->to(new Address($order->getBuyerEmail()))
            ->htmlTemplate('eCommerce/frontEnd/order/confirmationEmail.html.twig')
            ->context([
                "order" => $order,
            ]);

        $this->mailer->send($email);

        $order->setSentConfirmationEmail(true);
        $this->em->persist($order);
        $this->em->flush();
    }

    public function sendConfirmationEmailAfterCreateOrderToAdmin(Order $order): void
    {
        if ($order->isSentConfirmationEmail()) {
            return;
        }
        $email = (new TemplatedEmail())
            ->subject($this->siteSettingService->getByConstantName('website-title') . ' | New Order #' . $order->getId())
            ->from(new Address($this->siteSettingService->getByConstantName("email-from"), $this->siteSettingService->getByConstantName('website-title')))
            ->to(new Address($this->siteSettingService->getByConstantName("admin-email")))
            ->htmlTemplate('eCommerce/frontEnd/order/confirmationEmail.html.twig')
            ->context([
                "order" => $order,
            ]);

        $this->mailer->send($email);
    }

}