<?php

namespace App\CurrencyBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Form\CurrencyType;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\CurrencyBundle\Service\ExchangeRateService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("")
 */
class CurrencyController extends AbstractController
{

    /**
     * @Route("/", name="currency_index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->firewall();

        return $this->render('currency/admin/currency/index.html.twig');
    }

    /**
     * @Route("/new", name="currency_new", methods={"GET", "POST"})
     */
    public function new(
        Request $request,
        ExchangeRateService $exchangeRateService
    ): Response {
        $this->firewall();

        $currency = new Currency();
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($currency);
            $this->em()->flush();

            $exchangeRateService->addNewExchangeRates();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('currency_index');
        }

        return $this->render('currency/admin/currency/new.html.twig', [
            'currency' => $currency,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing currency entity.
     *
     * @Route("/{id}/edit", name="currency_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Currency $currency): Response
    {
        $this->firewall();

        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($currency);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('currency_edit', ['id' => $currency->getId()]);
        }

        return $this->render('currency/admin/currency/edit.html.twig', [
            'currency' => $currency,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="currency_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Currency $currency): Response
    {
        $this->firewall();

        if ($currency->isDefault()) {
            $this->addFlash("error", "Can't deleted the default currency");

            return $this->redirectToRoute('currency_index');
        }


        $userName = $userService->getUserName();
        $currency->setDeletedBy($userName);
        $currency->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($currency);
        $this->em()->flush();

        return $this->redirectToRoute('currency_index');
    }

    /**
     * Lists all Currency entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="currency_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, CurrencyRepository $currencyRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $currencyRepository->filter($search, true);
        $currencies = $currencyRepository->filter($search, false, $start, $length);

        return $this->render("currency/admin/currency/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "currencies" => $currencies,
        ]);
    }

    /**
     * @Route("/change-default/{id}", name="currency_change_default", methods={"POST"})
     */
    public function changeDefault(CurrencyRepository $currencyRepository, Currency $currency): Response
    {
        $this->firewall();

        $currencyRepository->changeDefault($currency);

        return $this->redirectToRoute('currency_index');
    }

    private function firewall()
    {
        if (!SystemConfiguration::ENABLE_MULTI_CURRENCIES) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    }


}
