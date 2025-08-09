<?php

namespace App\NewShippingBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Form\ShippingPriceFirstAndExtraKgType;
use App\NewShippingBundle\Form\ShippingPriceSpecificWeightModelType;
use App\NewShippingBundle\Form\ShippingZonePriceType;
use App\NewShippingBundle\Model\FirstAndExtraKgConfigurationModel;
use App\NewShippingBundle\Model\SpecificWeightConfigurationModel;
use App\NewShippingBundle\Service\ShippingZonePriceCsvService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route("shipping-zone-price")
 */
class ShippingZonePriceController extends AbstractController
{


    /**
     * @Route("/shipping-time", requirements={"id" = "\d+"}, name="shipping_zone_price_shipping_times_index", methods={"GET"})
     */
    public function shippingTimes(): Response
    {
        $shippingTimes = $this->em()->getRepository(ShippingTime::class)->findAll();

        return $this->render('newShipping/admin/shippingZonePrice/shippingTime/index.html.twig', [
            "shippingTimes" => $shippingTimes,
        ]);
    }


    /**
     * @Route("/{shippingTime}", requirements={"shippingTime" = "\d+"}, name="shipping_zone_price_index", methods={"GET"})
     */
    public function index(ShippingTime $shippingTime): Response
    {

        $search = new \stdClass;
        $search->deleted = 0;
        $search->shippingTime = $shippingTime->getId();
        $count = $this->em()->getRepository(ShippingZonePrice::class)->filter($search, true);

        return $this->render('newShipping/admin/shippingZonePrice/index.html.twig', [
            "shippingZonePriceCount" => $count,
            "shippingTime" => $shippingTime,
        ]);
    }

    /**
     * Lists all ShippingZonePrice entities.
     *
     * @Route("/data/table/{shippingTime}", requirements={"siteCountry" = "\d+","shippingTime" = "\d+"}, defaults={"_format": "json"}, name="shipping_zone_price_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, ShippingTime $shippingTime): Response
    {

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;
        $search->shippingTime = $shippingTime->getId();

        $count = $this->em()->getRepository(ShippingZonePrice::class)->filter($search, true);
        $shippingZonePrices = $this->em()->getRepository(ShippingZonePrice::class)->filter($search, false, $start,
            $length);

        return $this->render("newShipping/admin/shippingZonePrice/datatable.json.twig", array(
                "recordsTotal" => $count,
                "recordsFiltered" => $count,
                "shippingZonePrices" => $shippingZonePrices,
            )
        );
    }

    /**
     * Creates a new shippingZonePrice entity.
     *
     * @Route("/new/{shippingTime}", requirements={"siteCountry" = "\d+", "shippingTime" = "\d+"}, name="shipping_zone_price_new", methods={"GET", "POST"})
     */
    public function new(Request $request, ShippingTime $shippingTime): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $shippingZonePrice = new ShippingZonePrice();
        $shippingZonePrice->setShippingTime($shippingTime);
        $form = $this->getFormType($shippingZonePrice);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($shippingZonePrice);
            $this->em()->flush();

            $this->updateHasRates($shippingZonePrice);

            $this->addFlash('success', 'Successfully saved');

            return $this->redirect($this->generateUrl('shipping_zone_price_edit', [
                    'id' => $shippingZonePrice->getId(),
                    'shippingTime' => $shippingTime->getId(),
                ]) . "#price-tab");
        }

        return $this->render('newShipping/admin/shippingZonePrice/new.html.twig', array(
            'shippingZonePrice' => $shippingZonePrice,
            'shippingTime' => $shippingTime,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing shippingZonePrice entity.
     *
     * @Route("/{shippingTime}/{id}/edit", requirements={"id" = "\d+", "shippingTime" = "\d+"}, name="shipping_zone_price_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request           $request,
        ShippingTime      $shippingTime,
        ShippingZonePrice $shippingZonePrice
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $defaultTab = "edit-tab";
        $form = $this->getFormType($shippingZonePrice);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($shippingZonePrice);
            $this->em()->flush();

            $this->updateHasRates($shippingZonePrice);

            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('shipping_zone_price_edit', [
                'id' => $shippingZonePrice->getId(),
                'shippingTime' => $shippingTime->getId(),
            ]);
        }

        $priceForm = $this->getPriceFormType($shippingZonePrice);
        $priceForm->handleRequest($request);
        if ($priceForm->isSubmitted() && $priceForm->isValid()) {

            $this->em()->persist($shippingZonePrice);
            $this->em()->flush();

            $this->updateHasRates($shippingZonePrice);

            $this->addFlash('success', 'Successfully updated');

            return $this->redirect($this->generateUrl('shipping_zone_price_edit',
                    [
                        'id' => $shippingZonePrice->getId(),
                        'shippingTime' => $shippingTime->getId(),
                    ]
                ) . "#price-tab");
        } elseif ($priceForm->isSubmitted() && !$priceForm->isValid()) {
            $defaultTab = "price-tab";
        }

        return $this->render('newShipping/admin/shippingZonePrice/edit.html.twig', array(
            "defaultTab" => $defaultTab,
            'shippingZonePrice' => $shippingZonePrice,
            'shippingTime' => $shippingTime,
            'form' => $form->createView(),
            'price_form' => $priceForm->createView(),
        ));
    }


    /**
     * @Route("/clone/{shippingTime}/{id}", requirements={"id" = "\d+", "shippingTime" = "\d+"}, name="shipping_zone_price_clone", methods={"GET", "POST"})
     */
    public function clone(
        Request           $request,
        ShippingTime      $shippingTime,
        ShippingZonePrice $shippingZonePrice
    ): Response
    {

        $newEntity = clone $shippingZonePrice;
        $form = $this->getFormType($newEntity, true);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newEntity->setCalculator($shippingZonePrice->getCalculator());
            $newEntity->setCurrency($shippingZonePrice->getCurrency());
            $this->em()->persist($newEntity);
            $this->em()->flush();


            $this->updateHasRates($newEntity);

            $this->addFlash('success', 'Successfully updated');

            return $this->redirect($this->generateUrl('shipping_zone_price_edit', [
                    'id' => $shippingZonePrice->getId(),
                    'shippingTime' => $shippingTime->getId(),
                ]) . "#price-tab");
        }

        return $this->render('newShipping/admin/shippingZonePrice/new.html.twig', array(
            'shippingZonePrice' => $shippingZonePrice,
            'shippingTime' => $shippingTime,
            'form' => $form->createView(),
        ));
    }


    /**
     * Deletes a shippingZonePrice entity.
     *
     * @Route("/{id}", name="shipping_zone_price_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ShippingZonePrice $shippingZonePrice): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->em()->remove($shippingZonePrice);
        $this->em()->flush();

        return $this->redirectToRoute('shipping_zone_price_index', [
            "shippingTime" => $shippingZonePrice->getShippingTime()->getId(),
        ]);
    }


    /**
     * @Route("/download-example-csv/{calculator}", name="shipping_zone_price_example_csv", methods={"GET"})
     */
    public function downloadExampleCSV(ShippingZonePriceCsvService $shippingZonePriceCsvService, $calculator): void
    {
        $shippingZonePriceCsvService->downloadExample($calculator);
    }

    /**
     * @Route("/import-csv/{shippingTime}/{calculator}", name="shipping_zone_price_import_csv", methods={"GET", "POST"})
     */
    public function importCSV(Request $request, ShippingZonePriceCsvService $shippingZonePriceCsvService, ShippingTime $shippingTime, $calculator): Response
    {

        if (!in_array($calculator, ShippingZonePrice::$calculators)) {
            throw  $this->createNotFoundException();
        }
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, array(
                "required" => true,
                "attr" => array(
                    "accept" => ".csv",
                    "class" => "file-styled",
                ),
                "constraints" => [
                    new NotBlank(),
                    new File([
                        'maxSize' => '1M',
                        'mimeTypes' => [
                            'text/plain',
                            'text/csv',
                            'application/vnd.ms-excel',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid CSV file',
                    ]),
                ],
            ))// If I remove this line data is submitted correctly
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $shippingZonePriceCsvService->import($shippingTime, $calculator, $file);

            return $this->redirectToRoute("shipping_zone_price_import_csv", [
                "shippingTime" => $shippingTime->getId(),
                "calculator" => $calculator,
            ]);
        }

        $calculatorName = array_search($calculator, ShippingZonePrice::$calculators);

        return $this->render('newShipping/admin/shippingZonePrice/importCSV.html.twig', array(
            'calculator' => $calculator,
            'calculatorName' => $calculatorName,
            'shippingTime' => $shippingTime,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/export-csv/{shippingTime}/{calculator}", name="shipping_zone_price_export_csv", methods={"GET"})
     */
    public function exportCSV(Request $request, ShippingTime $shippingTime, $calculator): Response
    {

        if (!in_array($calculator, ShippingZonePrice::$calculators)) {
            throw  $this->createNotFoundException();
        }

        $search = new \stdClass;
        $search->calculator = $calculator;
        $search->deleted = 0;
        $search->shippingTime = $shippingTime->getId();

        $shippingZonePrices = $this->em()->getRepository(ShippingZonePrice::class)->filter($search, false);
        switch ($calculator) {
            case ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG;
                $list = $this->getFirstKgExtraKgCsv($shippingZonePrices);
                break;
            case ShippingZonePrice::CALCULATOR_WEIGHT_RATE;
                $list = $this->getWeightRateCsv($shippingZonePrices);
                break;
        }

        $f = fopen('php://memory', 'w');
        // loop over the input array
        foreach ($list as $fields) {
            fputcsv($f, $fields, ",");
        }
        fseek($f, 0);

        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        $fileName = "shipping-price-" . $shippingTime->getName() . "-" . $calculator . '-' . date("Y-m-d");
        header('Content-Disposition: attachment; filename="' . str_replace(" ", "_", strtolower($fileName)) . '.csv";');
        fpassthru($f);

        exit;
    }

    private function getFormType(ShippingZonePrice $shippingZonePrice, $clone = false): FormInterface
    {
        $form = $this->createForm(ShippingZonePriceType::class, $shippingZonePrice);
        if ($shippingZonePrice->getId() == null) {
            $form->add('calculator', ChoiceType::class, [
                "placeholder" => "Choose an option",
                "attr" => [
                    "class" => "select-search",
                    "readonly" => $clone,
                ],
                "choices" => ShippingZonePrice::$calculators,
            ]);
        }

        return $form;
    }


    private function getPriceFormType(ShippingZonePrice $shippingZonePrice): FormInterface
    {
        if ($shippingZonePrice->getCalculator() == ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG) {
            $formType = ShippingPriceFirstAndExtraKgType::class;

            return $this->createForm($formType, $shippingZonePrice);
        } elseif ($shippingZonePrice->getCalculator() == ShippingZonePrice::CALCULATOR_WEIGHT_RATE) {
            $formType = ShippingPriceSpecificWeightModelType::class;

            return $this->createForm($formType, $shippingZonePrice);
        } else {
            throw new \Exception('Error $calculator value');
        }

    }

    private function updateHasRates(ShippingZonePrice $shippingZonePrice): bool
    {

        $hasRates = false;
        if ($shippingZonePrice->getCalculator() == ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG) {
            $hasRates = $this->getHasRateFirstKgExtraKg($shippingZonePrice);

        } elseif ($shippingZonePrice->getCalculator() == ShippingZonePrice::CALCULATOR_WEIGHT_RATE) {
            $hasRates = $this->getHasRateWeightRate($shippingZonePrice);
        }

        $shippingZonePrice->setHasRates($hasRates);
        $this->em()->persist($shippingZonePrice);
        $this->em()->flush();

        return true;
    }

    private function getHasRateFirstKgExtraKg($shippingZonePrice): bool
    {
        $configurationModel = new FirstAndExtraKgConfigurationModel($shippingZonePrice);

        return $configurationModel->hasRate();
    }

    private function getHasRateWeightRate($shippingZonePrice): bool
    {
        $configurationModel = new SpecificWeightConfigurationModel($shippingZonePrice);

        return $configurationModel->hasRate();
    }

    private function getFirstKgExtraKgCsv(array $shippingZonePrices): array
    {
        $list = [];
        $list[] = [
            "Source Shipping Zone Code",
            "Target Shipping Zone",
            "Currency Code",
            "Courier Code",
            "First no of kg",
            "First kg rate",
            "Extra kg rate",
            "More than kg",
            "More kg rate",
        ];
        foreach ($shippingZonePrices as $shippingZonePrice) {

            $configuration = new FirstAndExtraKgConfigurationModel($shippingZonePrice);
            $list[] = [
                $shippingZonePrice->getSourceShippingZone()->getId(),
                $shippingZonePrice->getTargetShippingZone()->getId(),
                $shippingZonePrice->getCurrency()->getCode(),
                $shippingZonePrice->getCourier()->getId(),
                $configuration->getFirstNoOfKg(),
                $configuration->getFirstKgRate(),
                $configuration->getExtraKgRate(),
                $configuration->getMoreThanKg(),
                $configuration->getMoreKgRate(),
            ];
        }

        return $list;
    }

    private function getWeightRateCsv(array $shippingZonePrices): array
    {
        $list = [];
        $list[] = [
            "Source Shipping Zone Code",
            "Target Shipping Zone",
            "Currency Code",
            "Courier Code",
            "Weight",
            "Rate",
            "Extra kg rate",
        ];

        foreach ($shippingZonePrices as $shippingZonePrice) {
            $configuration = new SpecificWeightConfigurationModel($shippingZonePrice);
            $sourceShippingZoneId = $shippingZonePrice->getSourceShippingZone()->getId();
            $targetShippingZoneId = $shippingZonePrice->getTargetShippingZone()->getId();
            $currencyCode = $shippingZonePrice->getCurrency()->getCode();
            $courierId = $shippingZonePrice->getCourier()->getId();
            foreach ($shippingZonePrice->getSpecificWeights() as $specificWeight) {
                $list[] = [
                    $sourceShippingZoneId,
                    $targetShippingZoneId,
                    $currencyCode,
                    $courierId,
                    $specificWeight->getWeight(),
                    $specificWeight->getRate(),
                    $configuration->getExtraKgRate(),
                ];
            }
        }

        return $list;
    }
}
