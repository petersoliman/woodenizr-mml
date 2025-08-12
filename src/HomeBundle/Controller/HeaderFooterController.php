<?php

namespace App\HomeBundle\Controller;


use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Service\SiteSettingService;
use App\HomeBundle\Utils\MailChimp;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\OccasionRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\CategoryWebsiteHeaderService;
use App\ProductBundle\Service\ProductSearchService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\ContainerParameterService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("")
 */
class HeaderFooterController extends AbstractController
{
    private ContainerParameterService $containerParameterService;

    public function __construct(EntityManagerInterface $em,ContainerParameterService $containerParameterService)
    {
        parent::__construct($em);
        $this->containerParameterService = $containerParameterService;
    }

    /**
     * @Route("/auto-complete", name="fe_filter_product_auto_complete_ajax", methods={"GET"})
     */
    public function autoCompleteAction(
        Request                 $request,
        TranslatorInterface     $translator,
        ProductSearchRepository $productSearchRepository,
        ProductSearchService    $productSearchService
    ): Response
    {
        $jsonArray = ["error" => false, "message" => null, "products" => []];

        $submittedToken = $request->query->get('_token');
        if (!$this->isCsrfTokenValid('search-auto-complete', $submittedToken)) {
            $jsonArray["error"] = true;
            $jsonArray["message"] = $translator->trans("invalid_token_msg");


            return $this->json($jsonArray);
        }
        $search = new \stdClass();
        $search->deleted = 0;
        $search->ordr = ["column" => 0, "dir" => "DESC"];;
        $search->autocomplete = true;
        $search->string = $request->get('str');

        if (strlen(trim($search->string)) < 4) {
            $jsonArray["message"] = 1;

            return $this->json($jsonArray);
        }

        $entities = $productSearchRepository->filter($search, false, 0, 12);

        foreach ($entities as $entity) {
            $title = $entity->getTitle();
            $object = $productSearchService->convertEntityToObject($entity);
            $object["title"] = ($title == null) ? $entity->getTitle() : $title;
            $jsonArray["products"][] = $object;
        }

        return $this->json($jsonArray);
    }

    /**
     * @Route("/subscribe", name="fe_subscribe", methods={"POST"})
     */
    public function subscribe(Request $request, TranslatorInterface $translator, SiteSettingService $siteSettingService): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('newsletter-token', $submittedToken)) {
            return $this->json(["error" => true, "message" => $translator->trans("invalid_token_msg")]);
        }
        $apiKey = $siteSettingService->getByConstantName("mailchimp-api-key");
        if (!Validate::not_null($apiKey)) {
            return $this->json(["error" => true, "message" => "There is no t APIKey"]);
        }
        $email = $request->request->get('_token');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mailChimp = new MailChimp($apiKey);
            $listId = $siteSettingService->getByConstantName("mailchimp-list-id");

            $mailChimp->call("lists/$listId/members", [
                'email_address' => $email,
                'status' => "subscribed",
            ]);

            return $this->json(["error" => false, "message" => $translator->trans("subscribed_successfully_msg")]);
        }

        return $this->json(["error" => true, "message" => $translator->trans("enter_valid_email_msg")]);
    }

    public function menu(
        Request                      $request,
        CategoryWebsiteHeaderService $categoryWebsiteHeaderService,
        OccasionRepository           $occasionRepository,
        ProductSearchRepository      $productSearchRepository,
                                     $cr = null
    ): Response
    {
        $data = [];
        $data['mainCategories'] = $this->getHeaderCategories($request, $categoryWebsiteHeaderService);
        $data['occasion'] = $this->getActiveOccasion($request, $occasionRepository);
        $data['hasOnSale'] = $this->hasOnSaleProducts($productSearchRepository);
        $data["cr"] = $cr;

        return $this->render('fe/_menu.html.twig', $data);
    }

    public function footer(Request $request): Response
    {
        return $this->render('fe/_footer.html.twig');
    }

    private function getHeaderCategories(Request $request, CategoryWebsiteHeaderService $categoryWebsiteHeaderService): array
    {
        return $categoryWebsiteHeaderService->getData($request->getLocale());
    }

    private function getActiveOccasion(Request $request, OccasionRepository $occasionRepository)
    {
        $cacheKey = 'menu.occasion' . $request->getLocale();
        $cache = new FilesystemAdapter(directory: $this->containerParameterService->get("kernel.cache_dir") . "/filesystemAdapter-cache");
        return $cache->get($cacheKey, function (ItemInterface $item) use ($occasionRepository) {
            $item->expiresAfter(86400);// expire after 24 hrs

            return $occasionRepository->getActiveOccasion();
        });
    }

    private function hasOnSaleProducts(ProductSearchRepository $productSearchRepository): bool
    {
        $cacheKey = 'menu.on-sale';
        $cache = new FilesystemAdapter(directory: $this->containerParameterService->get("kernel.cache_dir") . "/filesystemAdapter-cache");
        return $cache->get($cacheKey, function (ItemInterface $item) use ($productSearchRepository) {
            $item->expiresAfter(3600);// expire after 1 hr

            $search = new \stdClass();
            $search->offer = true;

            $count = $productSearchRepository->filter($search, true);
            return $count > 0;
        });
    }
}
