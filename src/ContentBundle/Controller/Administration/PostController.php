<?php

namespace App\ContentBundle\Controller\Administration;

use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use PN\ServiceBundle\Service\ContainerParameterService;

/**
 * Custom Post Controller for SEO updates only
 */
class PostController extends AbstractController
{
    private $imageClass;
    private $postClass;

    public function __construct(ContainerParameterService $containerParameterService)
    {
        $pnMediaImage = $containerParameterService->get('pn_media_image');
        $this->imageClass = $pnMediaImage['image_class'];
        $this->postClass = $containerParameterService->get('pn_content_post_class');
    }

    /**
     * update image SEO (alt, title, filename)
     *
     * @Route("/gallery-seo-update/{post}", name="app_post_update_image_seo_ajax", methods={"POST"})
     */
    public function updateImageSeoAction(
        Request $request,
        $post,
        EntityManagerInterface $em
    ): JsonResponse {
        error_log("=== CUSTOM SEO UPDATE METHOD CALLED ===");
        error_log("Request data: " . json_encode($request->request->all()));
        error_log("Image class: " . $this->imageClass);
        error_log("Post class: " . $this->postClass);

        $imageId = $request->request->get('image_id');
        $alt = $request->request->get('alt');
        $title = $request->request->get('title');
        $filename = $request->request->get('filename');

        // Validate required fields
        if (!Validate::not_null($alt)) {
            return $this->json(['error' => 1, 'message' => 'Alt text is required']);
        }

        $image = $em->getRepository($this->imageClass)->find($imageId);
        if (!$image) {
            return $this->json(['error' => 1, 'message' => 'Image not found']);
        }

        error_log("Image found: " . get_class($image));

        // Update image SEO fields
        $image->setAlt($alt);
        
        // Update title if provided
        if ($title) {
            $image->setTitle($title);
            error_log("Setting title: " . $title);
            error_log("Has setTitle: " . (method_exists($image, 'setTitle') ? 'YES' : 'NO'));
            error_log("Has getTitle: " . (method_exists($image, 'getTitle') ? 'YES' : 'NO'));
            if (method_exists($image, 'getTitle')) {
                error_log("Title after set: " . $image->getTitle());
            }
        }

        $em->persist($image);
        error_log("Image persisted");
        $em->flush();
        error_log("Database flushed");
        
        // Debug: Check if title was actually saved
        if (method_exists($image, 'getTitle')) {
            error_log("Final title value: " . $image->getTitle());
        }

        return $this->json([
            'error' => 0,
            'message' => 'Image SEO updated successfully',
            'alt' => $alt,
            'title' => $title,
            'filename' => $filename,
            'debug_class' => get_class($image)
        ]);
    }
}
