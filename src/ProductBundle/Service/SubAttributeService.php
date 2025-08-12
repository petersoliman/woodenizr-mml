<?php

namespace App\ProductBundle\Service;

use PN\ServiceBundle\Lib\Paginator;
use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Entity\ProductHasAttribute;
use App\ProductBundle\Entity\SubAttribute;
use App\ProductBundle\Form\SubAttributeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubAttributeService
{

    private EntityManagerInterface $em;
    private UrlGeneratorInterface $urlGenerator;
    private FormFactoryInterface $formFactory;
    
    public function __construct(
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        FormFactoryInterface $formFactory
    ) {
        $this->formFactory = $formFactory;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }

    public function getEditSubAttributesForm(Attribute $attribute, $page = 1): \stdClass
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->attribute = $attribute->getId();
        $search->ordr = ["column" => 0, "dir" => "DESC"];

        $count = $this->em->getRepository(SubAttribute::class)->filter($search, true);
        $paginator = new Paginator($count, $page, 20);
        $subAttributes = $this->em->getRepository(SubAttribute::class)->filter($search, false,
            $paginator->getLimitStart(),
            $paginator->getPageLimit());
        foreach ($subAttributes as $subAttribute) {
            $noOfUsage = $this->em->getRepository(ProductHasAttribute::class)->countBySubAttribute($subAttribute);
            $subAttribute->isUsed = ($noOfUsage > 0) ? true : false;
        }
        $form = $this->createFormBuilder($attribute)
            ->setAction($this->generateUrl("sub_attribute_edit", ["id" => $attribute->getId(), "page" => $page]))
            ->add("subAttributes", CollectionType::class, [
                'entry_type' => SubAttributeType::class,
                "data" => $subAttributes,
                "allow_delete" => false,
                "mapped" => false,
                "label" => false,
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (array_key_exists('subAttributes', $data)) {
                    $subAttributes = $data['subAttributes'];
                    $sortedSubAttributes = [];

                    foreach ($subAttributes as $subAttribute) {
                        $sortedSubAttributes[] = $subAttribute;
                    }
                    $data['subAttributes'] = $sortedSubAttributes;
                    $event->setData($data);
                }
            })
            ->getForm();

        $return = new \stdClass();
        $return->paginator = $paginator;
        $return->form = $form;

        return $return;
    }

    private function createFormBuilder($data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createBuilder(FormType::class, $data, $options);
    }

    private function generateUrl(
        $route,
        $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }
}
