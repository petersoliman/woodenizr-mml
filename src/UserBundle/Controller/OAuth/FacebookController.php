<?php

namespace App\UserBundle\Controller\OAuth;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

class FacebookController extends AbstractOAuthController
{


    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/facebook", name="connect_facebook_start")
     */
    public function connect(ClientRegistry $clientRegistry): Response
    {
        // will redirect to Facebook!
        return $clientRegistry
            ->getClient('facebook') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'public_profile', 'email' // the scopes you want to access
            ]);
    }

    /**
     * After going to Facebook, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/connect/facebook/check", name="connect_facebook_check")
     */
    public function connectCheckAction(
        Request        $request,
        ClientRegistry $clientRegistry
    ): Response
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var FacebookClient $client */
        $client = $clientRegistry->getClient('facebook');

        try {
            // the exact class depends on which provider you're using
            /** @var FacebookUser $user */
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
