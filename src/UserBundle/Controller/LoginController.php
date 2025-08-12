<?php

namespace App\UserBundle\Controller;

use App\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginController extends AbstractController
{
    use TargetPathTrait;


    /**
     * @Route("/login", name="app_user_login")
     */
    public function login(
        Request              $request,
        AuthenticationUtils  $authenticationUtils,
        FirewallMapInterface $firewallMap,
        TranslatorInterface  $translator
    ): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('fe_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $targetPath = $this->onAuthenticationSuccess($request, $firewallMap);


        return $this->render('user/login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'targetPath' => $targetPath,
            "breadcrumbs" => $this->breadcrumbs($translator)
        ]);
    }

    /**
     * @Route("/logout", name="app_user_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function onAuthenticationSuccess(Request $request, FirewallMapInterface $firewallMap): string
    {
        $firewallConfig = $firewallMap->getFirewallConfig($request);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallConfig->getName())) {
            return $targetPath;
        }

        return $this->generateUrl('fe_home');
    }

    private function breadcrumbs(TranslatorInterface $translator): array
    {
        return [
            [
                "title" => $translator->trans("home_txt"),
                "url" => $this->generateUrl("fe_home"),
            ],
            [
                "title" => $translator->trans("login_txt"),
                "url" => null,
            ],
        ];
    }
}
