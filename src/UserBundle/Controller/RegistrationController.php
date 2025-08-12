<?php

namespace App\UserBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\Form\RegistrationType;
use App\UserBundle\Security\CustomAuthenticator;
use App\UserBundle\UserEvents;
use PN\ServiceBundle\Utils\General;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    use TargetPathTrait;

    /**
     * @Route("/register", name="app_user_registration")
     */
    public function register(
        Request                    $request,
        EventDispatcherInterface   $eventDispatcher,
        UserAuthenticatorInterface $userAuthenticator,
        CustomAuthenticator        $authenticator,
        FirewallMapInterface       $firewallMap,
        TranslatorInterface  $translator
    ): Response
    {
        //if user is already logged in just redirect him to home and tell him that he needs to log out first
        if ($this->getUser()) {
            $this->addFlash('warning',
                'You are already logged in as a user, please logout if you want to create another account with different credentials');

            return $this->redirectToRoute('fe_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$form->has("plain")) {
                $user->setPlainPassword(General::generateRandString());
            }
            if ($request->getSession()->has("AOuthData")) {
                $AOuthData = $request->getSession()->get("AOuthData");
                $methodName = "set" . ucfirst($AOuthData['provider']) . "Id";
                $user->$methodName($AOuthData['id']);
            }

            // persisting and adding the user to the database
            $this->em()->persist($user);
            $this->em()->flush();

            $this->addFlash("success", "Signed up successfully");

            $event = new RegistrationEvent($user, $request);
            $eventDispatcher->dispatch($event, UserEvents::REGISTRATION_COMPLETED);

            $userAuthenticator->authenticateUser($user, $authenticator, $request);

            return $this->onAuthenticationSuccess($request, $firewallMap);
        }

        return $this->render('user/registration/index.html.twig', [
            'form' => $form->createView(),
            "breadcrumbs" => $this->breadcrumbs($translator)
        ]);
    }


    private function onAuthenticationSuccess(Request $request, FirewallMapInterface $firewallMap): ?Response
    {
        $firewallConfig = $firewallMap->getFirewallConfig($request);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallConfig->getName())) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->generateUrl('fe_home'));
    }
    private function breadcrumbs(TranslatorInterface $translator): array
    {
        return [
            [
                "title" => $translator->trans("home_txt"),
                "url" => $this->generateUrl("fe_home"),
            ],
            [
                "title" => $translator->trans("signup_txt"),
                "url" => null,
            ],
        ];
    }
}
