<?php

namespace App\ThreeSixtyViewBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\MediaBundle\Entity\Image;
use App\MediaBundle\Repository\ImageRepository;
use App\ThreeSixtyViewBundle\Entity\ThreeSixtyView;
use App\ThreeSixtyViewBundle\Form\ThreeSixtyViewType;
use App\ThreeSixtyViewBundle\Repository\ThreeSixtyViewRepository;
use App\ThreeSixtyViewBundle\Service\ThreeSixtyViewService;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class ThreeSixtyViewController extends AbstractController
{

    private int $imageWidth = 1000;
    private int $imageHeight = 1000;

    /**
     * @Route("/{id}/images", name="three_sixty_view_images", methods={"GET", "POST"})
     */
    public function images(
        Request $request,
        ThreeSixtyViewService $threeSixtyViewService,
        ThreeSixtyView $threeSixtyView
    ): Response {
        $entity = $threeSixtyViewService->getRelationalEntity($threeSixtyView);

        $form = $this->createForm(ThreeSixtyViewType::class, $threeSixtyView);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($threeSixtyView);
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('three_sixty_view_images', ["id" => $threeSixtyView->getId()]);
        }

        return $this->render("threeSixtyView/admin/threeSixtyView/images.html.twig", [
            "threeSixtyView" => $threeSixtyView,
            "form" => $form->createView(),
            "entity" => $entity,
        ]);
    }

    /**
     * @Route("/{id}/upload", name="three_sixty_view_images_upload_images", methods={"POST"})
     */
    public function uploadImages(
        Request $request,
        ThreeSixtyViewService $threeSixtyViewService,
        ThreeSixtyView $threeSixtyView
    ): Response {
        $returnData = [];
//        $files = $request->files->get('files');
        $files = [$request->files->get('file')];
        foreach ($files as $file) {
            $validateImageDimension = $this->validateImageDimension($file);
            if (!$validateImageDimension) {
                $message = "This image dimensions are wrong, please upload one with the right dimensions";

                return $this->json(["error" => $message]);
            }
            $imageName = $this->getNewUploadImageName($threeSixtyView);
            $imageNumber = $this->getNewImageNumber($threeSixtyView);
            $uploadPath = "360-view/".$threeSixtyView->getId();
            $image = $this->uploadImage($file, $uploadPath, $imageName);
            $this->setImageInfo($image, $imageNumber);

            $this->renameImage($image, $imageName, true);
            $threeSixtyView->addImage($image);
            $this->em()->persist($threeSixtyView);
            $this->em()->flush();
            $returnData [] = $this->renderView("threeSixtyView/admin/threeSixtyView/imageItem.html.twig", [
                "image" => $image,
                "threeSixtyView" => $threeSixtyView,
            ]);
        }

        return $this->json($returnData);
    }

    /**
     * @Route("/{id}/images-sort-ajax", name="three_sixty_view_images_sort_sort_ajax", methods={"POST"})
     */
    public function sort(
        Request $request,
        ThreeSixtyView $threeSixtyView,
        ImageRepository $imageRepository,
        ThreeSixtyViewRepository $threeSixtyViewRepository
    ): Response {
        $sortedList = $request->request->get('image');
        $i = 1;
        $renameImagesPaths = [];
        foreach ($sortedList as $value) {
            $image = $imageRepository->find($value);
            if (!$threeSixtyView->hasImage($image)) {
                continue;
            }

            $imageNumber = $i;
            if ($imageNumber < 10) {
                $imageNumber = "0".$i;
            }
            $imageName = "image-".$imageNumber;
            $image->setTarteb($i);

            $oldPath = $image->getAbsoluteExtension();
            $oldThumbPath = $image->getAbsoluteResizeExtension();


            $this->renameImage($image, $imageName);


            $newPath = $image->getAbsoluteExtension();
            $newThumbPath = $image->getAbsoluteResizeExtension();

            $renameImagesPaths[$oldPath] = $newPath;
            $renameImagesPaths[$oldThumbPath] = $newThumbPath;
            $this->em()->persist($image);

            $i++;
        }
        $this->em()->flush();
        $this->renameImageFlush($renameImagesPaths);
        $threeSixtyView = $threeSixtyViewRepository->find($threeSixtyView->getId());

        $images = [];
        foreach ($threeSixtyView->getImages() as $imageKey => $image) {
            $images[] = $this->renderView("threeSixtyView/admin/threeSixtyView/imageItem.html.twig", [
                "image" => $image,
                "threeSixtyView" => $threeSixtyView,
            ]);
        }

        $return = [
            'error' => 0,
            'message' => 'Successfully sorted',
            "images" => $images,
        ];

        return $this->json($return);
    }

    /**
     * @Route("/delete-all-image/{id}", name="three_sixty_view_images_all_delete", methods={"POST"})
     */
    public function deleteAllImage(ThreeSixtyView $threeSixtyView): Response
    {

        foreach ($threeSixtyView->getImages() as $image) {
            $threeSixtyView->removeImage($image);
            $this->em()->persist($threeSixtyView);
            $this->em()->remove($image);
        }
        $this->em()->flush();

        return $this->redirectToRoute('three_sixty_view_images', ["id" => $threeSixtyView->getId()]);
    }


    /**
     * @Route("/delete-image/{id}/{imageId}", name="three_sixty_view_images_delete", methods={"POST"})
     */
    public function deleteImage(
        Request $request,
        ImageRepository $imageRepository,
        ThreeSixtyView $threeSixtyView,
        $imageId
    ): Response {

        $image = $imageRepository->find($imageId);
        if (!$image instanceof Image) {
            throw $this->createNotFoundException('Unable to find Team entity.');
        }

        $threeSixtyView->removeImage($image);
        $this->em()->persist($threeSixtyView);
        $this->em()->flush();

        $this->em()->remove($image);
        $this->em()->flush();

        $i = 1;
        $renameImagesPaths = [];
        foreach ($threeSixtyView->getImages() as $image) {
            $imageNumber = $i;
            if ($imageNumber < 10) {
                $imageNumber = "0".$i;
            }
            $imageName = "image-".$imageNumber;
            $image->setTarteb($i);

            $oldPath = $image->getAbsoluteExtension();
            $oldThumbPath = $image->getAbsoluteResizeExtension();


            $this->renameImage($image, $imageName);


            $newPath = $image->getAbsoluteExtension();
            $newThumbPath = $image->getAbsoluteResizeExtension();

            $renameImagesPaths[$oldPath] = $newPath;
            $renameImagesPaths[$oldThumbPath] = $newThumbPath;
            $this->em()->persist($image);

            $i++;
        }
        $this->em()->flush();
        $this->renameImageFlush($renameImagesPaths);

        return $this->redirectToRoute('three_sixty_view_images', ["id" => $threeSixtyView->getId()]);
    }


    private function validateImageDimension(UploadedFile $file): bool
    {
        return true;

        $originalPath = $file->getPathname();
        $height = $this->imageHeight;
        $width = $this->imageWidth;

        list($currentWidth, $currentHeight) = getimagesize($originalPath);

        if ($width != null and $currentWidth != $width) {
            return false;
        }
        if ($height != null and $currentHeight != $height) {
            return false;
        }

        return true;
    }

    private function getNewImageNumber(ThreeSixtyView $color): int
    {
        $imageNumber = 1;
        if ($color->getImages()->count() > 0) {
            $lastImage = $color->getImages()->last();
            $imageNumber = $lastImage->getTarteb() + 1;
        }

        return (int)$imageNumber;
    }

    private function getNewUploadImageName(ThreeSixtyView $color): string
    {
        $imageName = "image-";
        $imageNumber = 1;
        if ($color->getImages()->count() > 0) {
            $imageNumber = $this->getNewImageNumber($color);
        }

        /*if ($imageNumber < 10) {
            $imageNumber = "0".$imageNumber;
        }*/

        return $imageName.$imageNumber;
    }

    private function uploadImage(File $file, $uploadPath, $imageName = null): Image
    {
        $image = new Image();
        $this->em()->persist($image);
        $this->em()->flush();
        $image->setFile($file);
        $image->setImageType(Image::TYPE_MAIN);
        $image->preUpload($imageName);
        $image->upload($uploadPath);

        return $image;
    }

    private function setImageInfo(Image $image, $imageNumber)
    {
        $originalPath = $image->getUploadRootDirWithFileName();
        $size = filesize($originalPath);
        list($width, $height) = getimagesize($originalPath);
        $image->setWidth($width);
        $image->setHeight($height);
        $image->setSize($size);
        $image->setTarteb($imageNumber);
    }

    private function renameImage(Image $image, $newName, $flushRenameFiles = false)
    {
        $oldPath = $image->getAbsoluteExtension();
        $oldThumbPath = $image->getAbsoluteResizeExtension();

        $extension = $image->getNameExtension();
        $image->setName($newName.'.'.$extension);

        $newPath = $image->getAbsoluteExtension();
        $newThumbPath = $image->getAbsoluteResizeExtension();

        if ($flushRenameFiles) {

            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
            }

            if (file_exists($oldThumbPath)) {
                rename($oldThumbPath, $newThumbPath);
            }
        }
    }

    private function renameImageFlush(array $images): void
    {
        foreach ($images as $oldPath => $newPath) {
            if (file_exists($oldPath)) {
                rename($oldPath, $newPath."-old");
            }
        }

        foreach ($images as $oldPath => $newPath) {
            if (file_exists($newPath."-old")) {
                rename($newPath."-old", $newPath);
            }

        }
    }
}
