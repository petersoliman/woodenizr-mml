<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Banner;
use App\CMSBundle\Form\BannerType;
use App\CMSBundle\Repository\BannerRepository;
use PN\MediaBundle\Service\UploadImageService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("banner")
 */
class BannerController extends AbstractController
{

    /**
     * @Route("/", name="banner_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('cms/admin/banner/index.html.twig');
    }

    /**
     * @Route("/new", name="banner_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UploadImageService $uploadImageService): Response
    {
        $banner = new Banner();
        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($banner);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $banner);
            if ($uploadImage === false) {
                return $this->redirectToRoute('banner_edit', ["id" => $banner->getId()]);
            }
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('banner_index');
        }

        return $this->render('cms/admin/banner/new.html.twig', [
            'banner' => $banner,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/edit", name="banner_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request $request,
        UploadImageService $uploadImageService,
        Banner $banner
    ): Response {
        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($banner);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $banner);
            if ($uploadImage === false) {
                return $this->redirectToRoute('banner_edit', ['id' => $banner->getId()]);
            }
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('banner_index');
        }

        return $this->render('cms/admin/banner/edit.html.twig', [
            'banner' => $banner,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="banner_delete", methods={"DELETE"})
     */
    public function delete(Banner $banner): Response
    {
        $this->em()->remove($banner);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('banner_index');
    }

    /**
     * Lists all Banner entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="banner_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, BannerRepository $bannerRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];

        $count = $bannerRepository->filter($search, true);
        $banners = $bannerRepository->filter($search, false, $start, $length);

        return $this->render("cms/admin/banner/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "banners" => $banners,
        ]);
    }

    private function uploadImage(
        Request $request,
        UploadImageService $uploadImageService,
        FormInterface $form,
        Banner $banner
    ): bool|string|\PN\MediaBundle\Entity\Image {
        $placementDimensions = $banner->getPlacement()->dimension();
        $width = $placementDimensions['width'];
        $height = $placementDimensions['height'];

        $file = $form->get("image")->get("file")->getData();
        if (!$file instanceof UploadedFile) {
            return true;
        }
        list($currentWidth, $currentHeight) = getimagesize($file->getRealPath());

        if ($width != null and $currentWidth != $width) {
            $this->addFlash("error", "This image dimensions are wrong, please upload one with the right dimensions");

            return false;
        }
        if ($height != null and $currentHeight != $height) {
            $this->addFlash("error", "This image dimensions are wrong, please upload one with the right dimensions");

            return false;
        }

        return $uploadImageService->uploadSingleImage($banner, $file, 100, $request);
    }


}
