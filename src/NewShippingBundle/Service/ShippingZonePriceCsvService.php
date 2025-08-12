<?php

namespace App\NewShippingBundle\Service;

use App\CurrencyBundle\Entity\Currency;
use App\NewShippingBundle\Entity\Courier;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;
use App\NewShippingBundle\Model\FirstAndExtraKgConfigurationModel;
use App\NewShippingBundle\Model\SpecificWeightConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShippingZonePriceCsvService
{

    private EntityManagerInterface $em;
    private SessionInterface $session;
    private UserService $userService;
    private array $shippingZonePriceInserted = [];
    private array $shippingZonePriceDeleted = [];
    private array $shippingZones = [];
    private array $currencies = [];
    private array $couriers = [];

    public function __construct(EntityManagerInterface $em, UserService $userService, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->userService = $userService;
        $this->session = $requestStack->getSession();
    }

    public function import(ShippingTime $shippingTime, string $calculator, UploadedFile $file): bool
    {
        if (!in_array($calculator, ShippingZonePrice::$calculators)) {
            throw new NotFoundHttpException();
        }

        $validate = $this->validateCsv($file, $calculator);
        if (!$validate) {
            return false;
        }

        $handle = fopen($file->getPathname(), 'r');
        $rowNumber = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($rowNumber == 1) {
                continue;
            }
            $object = $this->convertArrayToObj($calculator, $data);
            $sourceShippingZone = $this->getShippingZone($object->sourceShippingZone);
            $targetShippingZone = $this->getShippingZone($object->targetShippingZone);
            $currency = $this->getCurrency($object->currencyCode);
            $courier = $this->getCourier($object->courier);
            if ($calculator == ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG) {

                $this->createOrUpdateFirstKgShippingZonePrice(
                    $sourceShippingZone,
                    $targetShippingZone,
                    $shippingTime,
                    $currency,
                    $courier,
                    $object);
            } elseif ($calculator == ShippingZonePrice::CALCULATOR_WEIGHT_RATE) {
                $this->createOrUpdateWeightRateShippingZonePrice(
                    $sourceShippingZone,
                    $targetShippingZone,
                    $shippingTime,
                    $currency,
                    $courier,
                    $object);
            }

        }
        $this->em->flush();
        $this->addFlash("success",
            "Shipping prices added successfully, No of Prices added <strong><u>" . ($rowNumber - 1) . "</u></strong>");

        return true;
    }

    private function convertArrayToObj(string $calculator, array $row): \stdClass
    {

        $object = new \stdClass();
        $object->sourceShippingZone = isset($row[0]) ? $row[0] : null;
        $object->targetShippingZone = isset($row[1]) ? $row[1] : null;
        $object->currencyCode = isset($row[2]) ? $row[2] : null;
        $object->courier = isset($row[3]) ? $row[3] : null;

        if ($calculator == ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG) {
            $object->firstNoOfKg = isset($row[4]) ? $row[4] : 1;
            $object->firstKgRate = isset($row[5]) ? $row[5] : null;
            $object->extraKgRate = isset($row[6]) ? $row[6] : null;
            $object->moreThanKg = isset($row[7]) ? $row[7] : null;
            $object->moreKgRate = isset($row[8]) ? $row[8] : null;
        } elseif ($calculator == ShippingZonePrice::CALCULATOR_WEIGHT_RATE) {
            $object->weight = isset($row[4]) ? $row[4] : null;
            $object->rate = isset($row[5]) ? $row[5] : null;
            $object->extraKgRate = isset($row[6]) ? $row[6] : null;
        }

        return $object;
    }

    private function validateCsv(UploadedFile $file, string $calculator): bool
    {
        $numberOfColumns = 0;
        switch ($calculator) {
            case ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG:
                $numberOfColumns = 9;
                break;
            case ShippingZonePrice::CALCULATOR_WEIGHT_RATE:
                $numberOfColumns = 7;
                break;
        }

        $errors = [];
        $rowNumber = 0;

        $handle = fopen($file->getPathname(), 'r');
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($rowNumber == 1) {
                if (count($data) != $numberOfColumns) {

                    $this->addFlash('error',
                        "Row No <strong><u>$rowNumber</u></strong> : Invalid Columns number must be <strong><u>$numberOfColumns</u></strong> Column");

                    return false;
                }
                continue;
            }

            $object = $this->convertArrayToObj($calculator, $data);

            if ($this->getShippingZone($object->sourceShippingZone) == null) {
                $errors[$rowNumber][] = "Invalid source shipping zone code";
            }

            if ($this->getShippingZone($object->targetShippingZone) == null) {
                $errors[$rowNumber][] = "Invalid target shipping zone code";
            }
            if ($this->getCurrency($object->currencyCode) == null) {
                $errors[$rowNumber][] = "Invalid currency code";
            }
            if ($this->getCourier($object->courier) == null) {
                $errors[$rowNumber][] = "Invalid courier code";
            }

            if ($calculator == ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG) {
                if (!Validate::not_null($object->firstNoOfKg)) {
                    $errors[$rowNumber][] = "First no of kg can't be empty";
                } elseif (!is_float($object->firstNoOfKg) and !is_numeric($object->firstNoOfKg)) {
                    $errors[$rowNumber][] = "First no of kg is not valid";
                }

                if (!Validate::not_null($object->firstKgRate)) {
                    $errors[$rowNumber][] = "First kg rate can't be empty";
                } elseif (!is_float($object->firstKgRate) and !is_numeric($object->firstKgRate)) {
                    $errors[$rowNumber][] = "First kg rate is not valid";
                }

                if (!Validate::not_null($object->extraKgRate)) {
                    $errors[$rowNumber][] = "Extra kg rate can't be empty";
                } elseif (!is_float($object->extraKgRate) and !is_numeric($object->extraKgRate)) {
                    $errors[$rowNumber][] = "Extra kg rate is not valid";
                }
                if (Validate::not_null($object->moreThanKg) and !is_float($object->moreThanKg) and !is_numeric($object->moreThanKg)) {
                    $errors[$rowNumber][] = "More than kg is not valid";
                }
                if (Validate::not_null($object->moreKgRate) and !is_float($object->moreKgRate) and !is_numeric($object->moreKgRate)) {
                    $errors[$rowNumber][] = "More kg rate is not valid";
                }
            } elseif ($calculator == ShippingZonePrice::CALCULATOR_WEIGHT_RATE) {
                if (!Validate::not_null($object->weight)) {
                    $errors[$rowNumber][] = "Weight can't be empty";
                } elseif (!is_float($object->weight) and !is_numeric($object->weight)) {
                    $errors[$rowNumber][] = "Weight is not valid";
                }

                if (!Validate::not_null($object->rate)) {
                    $errors[$rowNumber][] = "Rate can't be empty";
                } elseif (!is_float($object->rate) and !is_numeric($object->rate)) {
                    $errors[$rowNumber][] = "Rate is not valid";
                }

                if (!Validate::not_null($object->extraKgRate)) {
                    $errors[$rowNumber][] = "Extra kg rate can't be empty";
                } elseif (!is_float($object->extraKgRate) and !is_numeric($object->extraKgRate)) {
                    $errors[$rowNumber][] = "Extra kg rate is not valid";
                }
            }
        }
        if ($rowNumber <= 1) {
            $errors[0][] = "This File is empty";
        }
        if (count($errors) == 0) {
            return true;
        }
        $errorMsg = "";
        foreach ($errors as $rowNumber => $error) {
            $errorMsg .= 'Row Number ' . $rowNumber . ':';
            $errorMsg .= '<ul>';
            $errorMsg .= "<li>" . implode("<br>", $error) . "</li>";
            $errorMsg .= '</ul>';
        }
        $this->addFlash('error', $errorMsg);

        return false;

    }

    private function createOrUpdateFirstKgShippingZonePrice(
        ShippingZone $sourceShippingZone,
        ShippingZone $targetShippingZone,
        ShippingTime $shippingTime,
        Currency     $currency,
        Courier      $courier,
        \stdClass    $object
    ): void
    {
        $deletedIndexName = $shippingTime->getId();
        if (!array_key_exists($deletedIndexName, $this->shippingZonePriceDeleted)) {
            $this->em->getRepository(ShippingZonePrice::class)->deleteByTargetRootZoneAndShippingTimeAndCalculator(
                $shippingTime, ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG);
            $this->shippingZonePriceDeleted[$deletedIndexName] = true;
        }

        $firstNoOfKg = $object->firstNoOfKg;
        $firstKgRate = $object->firstKgRate;
        $extraKgRate = $object->extraKgRate;
        $moreThanKg = $object->moreThanKg;
        $moreKgRate = $object->moreKgRate;

        $configurationModel = new FirstAndExtraKgConfigurationModel(new ShippingZonePrice());
        $configurationModel->setFirstNoOfKg($firstNoOfKg);
        $configurationModel->setFirstKgRate($firstKgRate);
        $configurationModel->setExtraKgRate($extraKgRate);
        $configurationModel->setMoreThanKg($moreThanKg);
        $configurationModel->setMoreKgRate($moreKgRate);

        $shippingZonePrice = $this->createNewShippingZonePrice($sourceShippingZone, $targetShippingZone,
            $shippingTime, $currency, $courier, ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG, $configurationModel->getConfiguration());

        $this->em->persist($shippingZonePrice);

    }

    private function createOrUpdateWeightRateShippingZonePrice(
        ShippingZone $sourceShippingZone,
        ShippingZone $targetShippingZone,
        ShippingTime $shippingTime,
        Currency     $currency,
        Courier      $courier,
        \stdClass    $object
    ): void
    {
        $deletedIndexName = $shippingTime->getId();
        if (!array_key_exists($deletedIndexName, $this->shippingZonePriceDeleted)) {
            $this->em->getRepository(ShippingZonePrice::class)->deleteByTargetRootZoneAndShippingTimeAndCalculator(
                $shippingTime, ShippingZonePrice::CALCULATOR_WEIGHT_RATE);
            $this->shippingZonePriceDeleted[$deletedIndexName] = true;
        }

        $configurationModel = new SpecificWeightConfigurationModel(new ShippingZonePrice());
        $extraKgRate = $object->extraKgRate;
        $configurationModel->setExtraKgRate($extraKgRate);
        $shippingZonePrice = $this->createNewShippingZonePrice($sourceShippingZone, $targetShippingZone,
            $shippingTime, $currency, $courier, ShippingZonePrice::CALCULATOR_WEIGHT_RATE, $configurationModel->getConfiguration());

        $weight = $object->weight;
        $rate = $object->rate;

        $userName = $this->userService->getUserName();

        $specificWeight = new ShippingZonePriceSpecificWeight();
        $specificWeight->setShippingZonePrice($shippingZonePrice);
        $specificWeight->setRate($rate);
        $specificWeight->setWeight($weight);
        $specificWeight->setCreator($userName);
        $specificWeight->setModifiedBy($userName);
        $shippingZonePrice->addSpecificWeight($specificWeight);

        $this->em->persist($shippingZonePrice);
    }


    public function downloadExample(string $calculator): void
    {
        if (!in_array($calculator, ShippingZonePrice::$calculators)) {
            throw new NotFoundHttpException();
        }
        $list = [];

        switch ($calculator) {
            case ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG:
                $list = $this->getFirstKGCSVExample();
                break;
            case ShippingZonePrice::CALCULATOR_WEIGHT_RATE:
                $list = $this->getWeightRateCSVExample();
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
        header('Content-Disposition: attachment; filename="shipping-zone-price-' . $calculator . '-example.csv";');

        fpassthru($f);

        exit;
    }

    private function getFirstKGCSVExample(): array
    {
        return [
            [
                "Source Shipping Zone Code",
                "Target Shipping Zone",
                "Currency Code",
                "Courier Code",
                "First no of kg",
                "First kg rate",
                "Extra kg rate",
                "More than kg",
                "More kg rate",
            ],
            [
                1,
                1,
                "EGP",
                1,
                1,
                10,
                5,
                100,
                300,
            ],
            [
                1,
                2,
                "EGP",
                2,
                2,
                5,
                2,
                50,
                200,
            ],
        ];
    }

    private function getWeightRateCSVExample()
    {
        return [
            [
                "Source Shipping Zone Code",
                "Target Shipping Zone",
                "Currency Code",
                "Courier Code",
                "Weight",
                "Rate",
                "Extra kg rate",
            ],
            [
                1,
                1,
                "USD",
                2,
                0.5,
                13.0,
                10,
            ],
            [
                1,
                1,
                "USD",
                2,
                1,
                17,
                10,
            ],
            [
                1,
                1,
                "USD",
                2,
                1.5,
                22,
                10,
            ],
            [
                1,
                1,
                "USD",
                2,
                2,
                27,
                10,
            ],
            [
                1,
                1,
                "USD",
                2,
                2.5,
                32,
                10,
            ],
        ];
    }

    private function createNewShippingZonePrice(
        ShippingZone $sourceShippingZone,
        ShippingZone $targetShippingZone,
        ShippingTime $shippingTime,
        Currency     $currency,
        Courier      $courier,
        string       $calculator,
        ?array       $configuration
    ): ?ShippingZonePrice
    {
        $insertIndexName = $sourceShippingZone->getId() . "-" . $targetShippingZone->getId() . "" . $shippingTime->getId();
        if (array_key_exists($insertIndexName, $this->shippingZonePriceInserted)) {
            return $this->shippingZonePriceInserted[$insertIndexName];
        }

        $shippingZonePrice = new ShippingZonePrice();
        $shippingZonePrice->setSourceShippingZone($sourceShippingZone);
        $shippingZonePrice->setTargetShippingZone($targetShippingZone);
        $shippingZonePrice->setCurrency($currency);
        $shippingZonePrice->setCourier($courier);
        $shippingZonePrice->setShippingTime($shippingTime);
        $shippingZonePrice->setCalculator($calculator);
        $shippingZonePrice->setHasRates(true);
        $shippingZonePrice->setConfiguration($configuration);

        $userName = $this->userService->getUserName();

        $shippingZonePrice->setCreator($userName);
        $shippingZonePrice->setModifiedBy($userName);

        return $this->shippingZonePriceInserted[$insertIndexName] = $shippingZonePrice;
    }

    private function addFlash($type, $message): void
    {
        $this->session->getFlashBag()->add($type, $message);
    }


    private function getCurrency($currencyCode = null): ?Currency
    {
        if (!Validate::not_null($currencyCode)) {
            return null;
        } elseif (array_key_exists($currencyCode, $this->currencies)) {
            return $this->currencies[$currencyCode];
        }

        $currency = $this->em->getRepository(Currency::class)->findOneByCode($currencyCode);

        if (!$currency) {
            return null;
        }

        return $this->currencies[$currencyCode] = $currency;
    }

    private function getShippingZone($shippingZoneId = null): ?ShippingZone
    {
        if (!Validate::not_null($shippingZoneId)) {
            return null;
        } elseif (array_key_exists($shippingZoneId, $this->shippingZones)) {
            return $this->shippingZones[$shippingZoneId];
        }

        $shippingZone = $this->em->getRepository(ShippingZone::class)->findOneByShippingZoneIdAndParentZone($shippingZoneId);

        if (!$shippingZone) {
            return null;
        }

        if ($shippingZone->getZones()) {
            return $this->shippingZones[$shippingZoneId] = $shippingZone;
        }
    }

    private function getCourier($courierId = null): ?Courier
    {
        if (!Validate::not_null($courierId)) {
            return null;
        } elseif (array_key_exists($courierId, $this->couriers)) {
            return $this->couriers[$courierId];
        }

        $courier = $this->em->getRepository(Courier::class)->find($courierId);

        if (!$courier) {
            return null;
        }

        return $this->couriers[$courierId] = $courier;
    }
}
