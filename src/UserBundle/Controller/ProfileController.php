<?php

namespace App\UserBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\UserBundle\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $em;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $em)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->em = $em;
    }


    /**
     * @Route("", name="app_user_profile_edit")
     */
    public function edit(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        TranslatorInterface $translator
    ): Response {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        $user = $this->getUser();


        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $this->em()->persist($user);
                $this->em()->flush();

                $this->addFlash("success", "Saved Successfully");

                return $this->redirectToRoute('fe_profile_edit');
            }
            $this->em()->refresh($user);
        }

        return $this->render('user/profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
