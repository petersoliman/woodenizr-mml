<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Project;
use App\CMSBundle\Form\ProjectType;
use App\CMSBundle\Repository\ProjectRepository;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Project controller.
 *
 * @Route("project")
 */
class ProjectController extends AbstractController
{

    /**
     * Lists all project entities.
     *
     * @Route("/", name="project_index",methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('cms/admin/project/index.html.twig');
    }

    /**
     * Creates a new project entity.
     *
     * @Route("/new", name="project_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($project);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('post_set_images', ['id' => $project->getPost()->getId()]);
        }

        return $this->render('cms/admin/project/new.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing project entity.
     *
     * @Route("/{id}/edit", name="project_edit",methods={"GET", "POST"})
     */
    public function edit(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('project_edit', ['id' => $project->getId()]);
        }

        return $this->render('cms/admin/project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a project entity.
     *
     * @Route("/{id}", name="project_delete",methods={"DELETE"})
     */
    public function delete(UserService $userService, Project $project): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userName = $userService->getUserName();
        $project->setDeletedBy($userName);
        $project->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($project);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('project_index');
    }

    /**
     * Lists all Project entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="project_datatable",methods={"GET"})
     */
    public function dataTable(Request $request, ProjectRepository $projectRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->deleted = 0;

        $count = $projectRepository->filter($search, true);
        $projects = $projectRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/project/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "projects" => $projects,
        ]);
    }

}
