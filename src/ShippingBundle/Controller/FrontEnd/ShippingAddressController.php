<?php

namespace App\ShippingBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\NewShippingBundle\Entity\Zone;
use App\ShippingBundle\Entity\City;
use App\ShippingBundle\Entity\ShippingAddress;
use App\UserBundle\Entity\User;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("shipping-address")
 */
class ShippingAddressController extends AbstractController
{

    /**
     * @Route("/list", name="fe_shipping_address_list_api", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => "Please login"]);
        }

        return $this->json([
            "error" => false,
            "message" => null,
            "addresses" => $this->getAddressesObjects($user)
        ]);
    }


    /**
     * @Route("/make-default", name="fe_shipping_address_make_default_api", methods={"POST"})
     */
    public function makeDefault(Request $request, TranslatorInterface $translator): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => "Please login"]);
        }
        $id = $request->request->getInt("id");
        if (!Validate::not_null($id)) {
            return $this->json(["error" => true, "message" => "Enter Address ID"]);
        }
        $shippingAddress = $this->em()->getRepository(ShippingAddress::class)->find($id);
        if (!$shippingAddress instanceof ShippingAddress) {
            return $this->json(["error" => true, "message" => "Invalid Address ID"]);
        }

        if ($shippingAddress->getUser() !== $user) {
            return $this->json(["error" => true, "message" => "Invalid Address ID"]);
        }
        $this->em()->getRepository(ShippingAddress::class)->removeDefault($user);


        $this->em()->refresh($shippingAddress);
        $shippingAddress->setDefault(true);
        $this->em()->persist($shippingAddress);
        $this->em()->flush();

        return $this->json([
            "error" => false,
            "message" => $translator->trans("saved_successfully_txt"),
            "addresses" => $this->getAddressesObjects($user)
        ]);
    }

    /**
     * Creates a new courier entity.
     *
     * @Route("/new", name="fe_shipping_address_new_api", methods={"GET", "POST"})
     */
    public function new(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => "Please login"]);
        }
        $shippingAddress = new ShippingAddress();
        $shippingAddress->setUser($user);

        $postData = $this->collectPostData($request);
        $validate = $this->validatePostData($postData);
        if (count($validate) > 0) {
            return $this->json(["error" => true, "message" => implode(", ", $validate)]);
        }

        $shippingAddress->setTitle($postData->title);
        $shippingAddress->setMobileNumber($postData->mobileNumber);
        $shippingAddress->setFullAddress($postData->address);
        $shippingAddress->setZone($postData->zone);

        $isUserHasDefaultAddress = $this->em()->getRepository(ShippingAddress::class)->isUserHasDefaultAddress($user);
        if (!$isUserHasDefaultAddress) {
            $shippingAddress->setDefault(true);
        }

        $this->em()->persist($shippingAddress);
        $this->em()->flush();

        return $this->json([
            "error" => false,
            "message" => "Successfully saved",
            "addresses" => $this->getAddressesObjects($shippingAddress->getUser())
        ]);
    }

    /**
     * @Route("/edit", name="fe_shipping_address_edit_api", methods={"GET", "POST"})
     */
    public function edit(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => "Please login"]);
        }
        $id = $request->request->getInt("id");
        if (!Validate::not_null($id)) {
            return $this->json(["error" => true, "message" => "Enter Address ID"]);
        }
        $shippingAddress = $this->em()->getRepository(ShippingAddress::class)->find($id);
        if (!$shippingAddress instanceof ShippingAddress) {
            return $this->json(["error" => true, "message" => "Invalid Address ID"]);
        }

        if ($shippingAddress->getUser() !== $user) {
            return $this->json(["error" => true, "message" => "Invalid Address ID"]);
        }

        $postData = $this->collectPostData($request);
        $validate = $this->validatePostData($postData);
        if (count($validate) > 0) {
            return $this->json(["error" => true, "message" => implode(", ", $validate)]);
        }

        $shippingAddress->setTitle($postData->title);
        $shippingAddress->setMobileNumber($postData->mobileNumber);
        $shippingAddress->setFullAddress($postData->address);
        $shippingAddress->setZone($postData->zone);

        $this->em()->persist($shippingAddress);
        $this->em()->flush();


        return $this->json([
            "error" => false,
            "message" => "Successfully saved",
            "addresses" => $this->getAddressesObjects($shippingAddress->getUser())
        ]);
    }

    /**
     * @Route("/delete", name="fe_shipping_address_delete_api", methods={"POST"})
     */
    public function delete(Request $request, UserService $userService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(["error" => true, "message" => "Please login"]);
        }
        $id = $request->request->getInt("id");
        if (!Validate::not_null($id)) {
            return $this->json(["error" => true, "message" => "Enter Address ID"]);
        }
        $shippingAddress = $this->em()->getRepository(ShippingAddress::class)->find($id);
        if (!$shippingAddress instanceof ShippingAddress) {
            return $this->json(["error" => true, "message" => "Invalid Address ID"]);
        }

        if ($shippingAddress->getUser() !== $user) {
            return $this->json(["error" => true, "message" => "Invalid Address ID"]);
        }
        $shippingAddress->setDeletedBy($userService->getUserName());
        $shippingAddress->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($shippingAddress);
        $this->em()->flush();

        if ($shippingAddress->getDefault()) {
            $this->em()->getRepository(ShippingAddress::class)->makeFirstAddressDefault($user);
        }

        return $this->json([
            "error" => false,
            "message" => "Successfully deleted",
            "addresses" => $this->getAddressesObjects($user)
        ]);
    }

    private function collectPostData(Request $request): \stdClass
    {
        $data = new \stdClass();
        $data->title = $request->request->get("title");
        $data->zoneId = $request->request->getInt("zoneId");
        $data->mobileNumber = $request->request->get("mobileNumber");
        $data->address = $request->request->get("address");
        return $data;
    }


    private function validatePostData(\stdClass $data): array
    {
        $errors = [];

        if (!Validate::not_null($data->title)) {
            $errors[] = "Enter Address Title";
        }

        if (!Validate::not_null($data->mobileNumber)) {
            $errors[] = "Enter mobile number";
        }

        if (!Validate::not_null($data->address)) {
            $errors[] = "Enter your address";
        }
        if (!Validate::not_null($data->zoneId)) {
            $errors[] = "Select your zone";
        } else {
            $data->zone = $this->em()->getRepository(Zone::class)->findOneBy(["id" => $data->zoneId, "deleted" => null]);
//            $data->zone = $this->em()->getRepository(City::class)->findOneBy(["id" => $data->zoneId, "deleted" => null]);
            if (!$data->zone instanceof City and !$data->zone instanceof Zone) {
                $errors[] = "Invalid selected zone";
            }
        }

        return $errors;
    }

    private function getAddressesObjects(User $user): array
    {
        $shippingAddresses = $this->em()->getRepository(ShippingAddress::class)->getValidByUser($user);

        $addressesObjects = [];
        foreach ($shippingAddresses as $shippingAddress) {
            $addressesObjects[] = $shippingAddress->getObj();
        }
        return $addressesObjects;
    }


}
