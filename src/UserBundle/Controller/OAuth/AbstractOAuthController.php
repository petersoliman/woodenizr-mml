<?php

namespace App\UserBundle\Controller\OAuth;

use App\BaseBundle\Controller\AbstractController;
use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\Security\CustomAuthenticator;
use App\UserBundle\UserEvents;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Provider\GoogleUser;
use PN\ServiceBundle\Utils\General;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

abstract class AbstractOAuthController extends AbstractController
{
    use TargetPathTrait;

    private EventDispatcherInterface $eventDispatcher;
    private UserAuthenticatorInterface $userAuthenticator;
    private CustomAuthenticator $authenticator;
    private FirewallMapInterface $firewallMap;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        EntityManagerInterface      $em,
        EventDispatcherInterface    $eventDispatcher,
        UserAuthenticatorInterface  $userAuthenticator,
        CustomAuthenticator         $authenticator,
        FirewallMapInterface        $firewallMap,
        UserPasswordHasherInterface $userPasswordHasher
    )
    {
        parent::__construct($em);
        $this->eventDispatcher = $eventDispatcher;
        $this->userAuthenticator = $userAuthenticator;
        $this->authenticator = $authenticator;
        $this->firewallMap = $firewallMap;
        $this->userPasswordHasher = $userPasswordHasher;
    }


    protected function createUserOrLogin(Request $request, FacebookUser|GoogleUser $oAuthUser): Response
    {
        $aouthId = $this->getUserId($oAuthUser);
        $aouthName = $this->getUserName($oAuthUser);
        $aouthEmail = $this->getUserEmail($oAuthUser);

        $provider = $this->getProviderName($oAuthUser);


        $user = $this->em()->getRepository(User::class)->findOneBy([$provider . "Id" => $aouthId]);

        if (Validate::not_null($aouthEmail) and !$user instanceof User) {
            $user = $this->em()->getRepository(User::class)->findOneBy(["emailCanonical" => $aouthEmail]);

            if ($user instanceof User) {
                $methodName = "set" . ucfirst($provider) . "Id";
                $user->$methodName($aouthId);
                $this->em()->persist($user);
                $this->em()->flush();
            }
        }

        if ($user instanceof User) {
            //login
            if (!$user->isEnabled() or $user->getDeleted() instanceof \DateTimeInterface) {
                $error = new AuthenticationException("Account is disabled");
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $error);
                return $this->redirectToRoute("app_user_login");
            }
            return $this->loginUser($request, $user);
        } else {
            //create an account
            if (Validate::not_null($aouthEmail)) {
                $user = $this->createNewUser($oAuthUser);

                return $this->loginUser($request, $user);
            }
            // redirect to registration page
            $data = [
                "provider" => $provider,
                "id" => $aouthId,
                "name" => $aouthName,
                "email" => $aouthEmail
            ];
            $request->getSession()->set("AOuthData", $data);

            return $this->redirectToRoute("app_user_registration");
        }
    }

    private function createNewUser($oAuthUser): User
    {
        $provider = $this->getProviderName($oAuthUser);
        $aouthId = $this->getUserId($oAuthUser);
        $aouthName = $this->getUserName($oAuthUser);
        $aouthEmail = $this->getUserEmail($oAuthUser);

        $user = new  User();
        $methodName = "set" . ucfirst($provider) . "Id";
        $user->$methodName($aouthId);
        $user->setFullName($aouthName);
        $user->setEmail($aouthEmail);
        $user->setPlainPassword(General::generateRandString());
        $user->setCreator($user->getFullName());
        $user->setModifiedBy($user->getFullName());
        $user->setEnabled(true);
        $this->em()->persist($user);
        $this->em()->flush();
        return $user;
    }

    private function getUserId($oAuthUser): string
    {
        return $oAuthUser->getId();
    }

    private function getUserName($oAuthUser): string
    {
        return $oAuthUser->getName();
    }

    private function getUserEmail($oAuthUser): ?string
    {
        return $oAuthUser->getEmail();
    }

    private function getProviderName(FacebookUser|GoogleUser $oAuthUser): string
    {
        if ($oAuthUser instanceof FacebookUser) {
            return "facebook";
        } elseif ($oAuthUser instanceof GoogleUser) {
            return "google";
        }
        throw new \Exception("Invalid oAuth type");
    }

    private function loginUser(Request $request, $user): Response
    {
        $event = new RegistrationEvent($user, $request);
        $this->eventDispatcher->dispatch($event, UserEvents::REGISTRATION_COMPLETED);

        $this->userAuthenticator->authenticateUser($user, $this->authenticator, $request);

        return $this->onAuthenticationSuccess($request,);

    }

    private function onAuthenticationSuccess(Request $request): ?Response
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallConfig->getName())) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->generateUrl('fe_home'));
    }
}
