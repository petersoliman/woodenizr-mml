<?php

namespace App\UserBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\UserBundle\Form\ChangePasswordFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/change-password")
 */
class ChangePasswordController extends AbstractController
{
    /**
     * @Route("", name="app_user_change_password")
     */
    public function changePassword(
        Request                     $request,
        UserPasswordHasherInterface $userPasswordHasher,
        TranslatorInterface         $translator
    ): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        $user = $this->getUser();

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->em()->persist($user);
            $this->em()->flush();

            $this->addFlash("success", "Saved Successfully");

            return $this->redirectToRoute('app_user_change_password');
        }

        return $this->render('user/change_password/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
