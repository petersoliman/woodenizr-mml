<?php

namespace App\CMSBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\SiteSetting;
use App\CMSBundle\Enum\SiteSettingTypeEnum;
use App\CMSBundle\Form\SiteSettingType;
use App\CMSBundle\Repository\SiteSettingRepository;
use App\CMSBundle\Service\SiteSettingService;
use PN\MediaBundle\Service\ImagePaths;
use PN\ServiceBundle\Lib\UploadPath;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SiteSetting controller.
 *
 * @Route("/site-setting")
 */
class SiteSettingController extends AbstractController
{

    /**
     * @Route("/", name="site_setting_index", methods={"GET"})
     */
    public function indexAction(SiteSettingRepository $siteSettingRepository): Response
    {
        if ($this->isGranted("ROLE_SUPER_ADMIN")) {
            $siteSettings = $siteSettingRepository->findAll();
        } else {
            $siteSettings = $siteSettingRepository->findBy(["manageBySuperAdminOnly" => 0]);
        }

        return $this->render('cms/admin/siteSetting/index.html.twig', [
            'siteSettings' => $siteSettings,
        ]);
    }

    /**
     * @Route("/{constantName}/edit", name="site_setting_edit", methods={"GET", "POST"})
     */
    public function editAction(
        Request            $request,
        ImagePaths         $imagePaths,
        SiteSettingService $settingService,
        SiteSetting        $siteSetting
    ): Response
    {
        if (!$this->isGranted("ROLE_SUPER_ADMIN") and $siteSetting->isManageBySuperAdminOnly()) {
            throw $this->createAccessDeniedException();
        }
        $oldValue = $siteSetting->getValue();

        $form = $this->createForm(SiteSettingType::class, $siteSetting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($siteSetting->getType(), [SiteSettingTypeEnum::IMAGE, SiteSettingTypeEnum::FAVICON])) {
                if (Validate::not_null($oldValue)) {
                    $uploadDirectory = UploadPath::getUploadRootDir($siteSetting->getValue());
                    if (file_exists($uploadDirectory)) {
                        unlink($uploadDirectory);
                    }
                }

                $file = $form->get('value')->getData();
                if ($file) {
                    $uploadPath = date("Y/m/d") . "/" . ltrim($imagePaths->get(99), "/") . 'image/';
                    $uploadDirectory = UploadPath::getUploadRootDir($uploadPath);
                    $fileName = uniqid() . '.' . $file->guessExtension();
                    $file->move($uploadDirectory, $fileName);
                    $siteSetting->setValue("uploads/".$uploadPath . $fileName);
                }
            }

            $this->em()->persist($siteSetting);
            $this->em()->flush();

            $settingService->removeCache();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('site_setting_edit', ['constantName' => $siteSetting->getConstantName()]);
        }

        return $this->render('cms/admin/siteSetting/edit.html.twig', [
            'siteSetting' => $siteSetting,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/remove-cache", name="site_setting_remove_cache", methods={"GET", "POST"})
     */
    public function removeCache(SiteSettingService $settingService): Response
    {
        $this->addFlash("success", "Cache Removed Successfully");
        $settingService->removeCache();
        return $this->redirectToRoute("site_setting_index");

    }
}
