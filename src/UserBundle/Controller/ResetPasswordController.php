<?php

namespace App\UserBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\CMSBundle\Service\SiteSettingService;
use App\UserBundle\Entity\User;
use App\UserBundle\Form\ChangePasswordFormType;
use App\UserBundle\Form\ResetPasswordRequestFormType;
use App\UserBundle\Security\CustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/reset-password")
 */
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    use TargetPathTrait;


    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $em;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $em)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->em = $em;
    }

    /**
     * Display & process form to request a password reset.
     * @Route("", name="app_user_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer,
                $translator
            );
        }
        $breadcrumbs = [
            [
                "title" => $translator->trans("home_txt"),
                "url" => $this->generateUrl("fe_home"),
            ],
            [
                "title" => $translator->trans("forgot_password_question_txt"),
                "url" => null,
            ],
        ];

        return $this->render('user/reset_password/request.html.twig', [
            'form' => $form->createView(),
            "breadcrumbs" => $breadcrumbs

        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     * @Route("/check-email", name="app_user_check_email")
     */
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('user/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     * @Route("/reset/{token}", name="app_user_reset_password")
     */
    public function reset(
        Request                     $request,
        UserPasswordHasherInterface $userPasswordHasher,
        TranslatorInterface         $translator,
        UserAuthenticatorInterface  $userAuthenticator,
        CustomAuthenticator         $authenticator,
        FirewallMapInterface        $firewallMap,
        string                      $token = null
    ): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_user_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [],
                    'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_user_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->em->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            $userAuthenticator->authenticateUser($user, $authenticator, $request);

            return $this->onAuthenticationSuccess($request, $firewallMap);
        }


        $breadcrumbs = [
            [
                "title" => $translator->trans("home_txt"),
                "url" => $this->generateUrl("fe_home"),
            ],
            [
                "title" => $translator->trans("reset_password_txt"),
                "url" => null,
            ],
        ];
        return $this->render('user/reset_password/reset.html.twig', [
            'form' => $form->createView(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    private function processSendingPasswordResetEmail(
        string              $emailFormData,
        MailerInterface     $mailer,
        TranslatorInterface $translator,
        SiteSettingService $siteSettingService
    ): RedirectResponse
    {
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
            "enabled" => true,
            "deleted" => null,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_user_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->redirectToRoute('app_user_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address($siteSettingService->getByConstantName('email-from'), $siteSettingService->getByConstantName('website-title')))
            ->to($user->getEmail())
            ->subject($siteSettingService->getByConstantName('website-title') . ' | Your password reset request')
            ->htmlTemplate('user/reset_password/email.html.twig')
            ->context([
                'user' => $user,
                'resetToken' => $resetToken,
            ]);

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_user_check_email');
    }

    private function onAuthenticationSuccess(Request $request, FirewallMapInterface $firewallMap): ?Response
    {
        $firewallConfig = $firewallMap->getFirewallConfig($request);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallConfig->getName())) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->generateUrl('fe_home'));
    }
}
