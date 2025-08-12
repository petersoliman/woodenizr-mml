<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Team;
use App\CMSBundle\Form\TeamType;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Team controller.
 *
 * @Route("team")
 */
class TeamController extends AbstractController
{

    /**
     * Lists all Team entities.
     *
     * @Route("/", name="team_index", methods={"GET"})
     */
    public function indexAction()
    {

        return $this->render('cms/admin/team/index.html.twig');
    }

    /**
     * Creates a new Team entity.
     *
     * @Route("/new", name="team_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, UploadImageService $uploadImageService, UserService $userService)
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $userName = $userService->getUserName();
            $team->setCreator($userName);
            $team->setModifiedBy($userName);
            $this->em()->persist($team);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $team);
            if ($uploadImage) {
                $this->addFlash('success', 'Successfully saved');

                return $this->redirectToRoute('team_index');
            }

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('team_index');
        }

        return $this->render('cms/admin/team/new.html.twig', [
            'team' => $team,
            'form' => $form->createView(),
        ]);
    }

    private function uploadImage(
        Request $request,
        UploadImageService $uploadImageService,
        FormInterface $form,
        Team $entity
    ) {
        $file = $form->get("image")->get("file")->getData();
        if (!$file instanceof UploadedFile) {
            return false;
        }

        return $uploadImageService->uploadSingleImage($entity, $file, 103, $request);
    }

    /**
     * Displays a form to edit an existing Team entity.
     *
     * @Route("/{id}/edit", name="team_edit", methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        UploadImageService $uploadImageService,
        UserService $userService,
        Team $team
    ) {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $userService->getUserName();
            $team->setModifiedBy($userName);

            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $team);
            if ($uploadImage === false) {
                return $this->redirectToRoute('team_edit', ['id' => $team->getId()]);
            }
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('team_index');

        }

        return $this->render('cms/admin/team/edit.html.twig', [
            'team' => $team,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Team entity.
     *
     * @Route("/{id}", name="team_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, UserService $userService, Team $team)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $image = $team->getImage();
        if ($image) {
            $image->removeUpload();
        }

        $userName = $userService->getUserName();
        $team->setDeletedBy($userName);
        $team->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($team);
        $this->em()->flush();

        return $this->redirectToRoute('team_index');
    }

    /**
     * Lists all Team entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="team_datatable", methods={"GET"})
     */
    public function dataTableAction(Request $request)
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $this->em()->getRepository(Team::class)->filter($search, true);
        $teams = $this->em()->getRepository(Team::class)->filter($search, false, $start, $length);

        return $this->render("cms/admin/team/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "teams" => $teams,
        ]);
    }

}
