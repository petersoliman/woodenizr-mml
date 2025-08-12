<?php

namespace App\UserBundle\Controller\OAuth;

use App\BaseBundle\Controller\AbstractController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

class GoogleController extends AbstractOAuthController
{
    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/google", name="connect_google_start")
     */
    public function connect(ClientRegistry $clientRegistry):Response
    {
        // will redirect to Google!
        return $clientRegistry
            ->getClient('google') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([// the scopes you want to access
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile'
            ]);
    }

    /**
     * After going to Google, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/connect/google/check", name="connect_google_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry):Response
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        try {
            // the exact class depends on which provider you're using
            /** @var GoogleUser $user */
            $user = $client->fetchUser();
            return $this->createUserOrLogin($request, $user);
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            $error = new AuthenticationException($e->getMessage());
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $error);
            return $this->redirectToRoute("app_user_login");
        }
    }
}
