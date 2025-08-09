<?php

namespace App\ThreeSixtyViewBundle\Service;

use App\ThreeSixtyViewBundle\Entity\ThreeSixtyView;
use App\ThreeSixtyViewBundle\Enums\ThreeSixtyViewImageExtensionEnums;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class ThreeSixtyViewService
{
    private EntityManagerInterface $em;
    private RouterInterface $router;

    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    public function createNew(): ThreeSixtyView
    {
        $threeSixtyView = new ThreeSixtyView();
        $threeSixtyView->setImageExtension(ThreeSixtyViewImageExtensionEnums::IMAGE_EXTENSION_PNG);
        $this->em->persist($threeSixtyView);
        $this->em->flush();

        return $threeSixtyView;
    }

    /**
     * @throws \Exception
     */
    public function open(object $entity): RedirectResponse
    {
        if (!method_exists($entity, "getThreeSixtyView")) {
            throw new \Exception(sprintf("%class is not contain getThreeSixtyView() function", [$entity::class]));
        }

        $threeSixtyView = $entity->getThreeSixtyView();
        if (!$threeSixtyView instanceof ThreeSixtyView) {
            $threeSixtyView = $this->createNew();
        }

        return new RedirectResponse(
            $this->router->generate("three_sixty_view_images", ["id", $threeSixtyView->getId()])
        );
    }

    public function getRelationalEntity(ThreeSixtyView $threeSixtyView): ?object
    {
        $entities = $this->getEntitiesWithPostObject();
        $statement = $this->em->createQueryBuilder()
            ->from(ThreeSixtyView::class, "tsv")
            ->where("tsv.id=:threeSixtyViewId")
            ->setParameter("threeSixtyViewId", $threeSixtyView->getId());

        foreach ($entities as $key => $entity) {
            $alias = "t".$key;
            $statement->addSelect($alias);
            $statement->leftJoin($entity['entity'], $alias, "WITH", "tsv.id=".$alias.".".$entity['columnName']);
        }
        $result = $statement->getQuery()->getResult();
        foreach ($result as $row) {
            if (is_object($row)) {
                return $row;
            }
        }

        return null;
    }

    public function getRelationalEntityId(ThreeSixtyView $threeSixtyView): ?int
    {
        $entity = $this->getRelationalEntity($threeSixtyView);
        if (is_object($entity)) {
            return $threeSixtyView->getId();
        }

        return null;
    }

    private function getEntitiesWithPostObject(): array
    {
        $entities = [];
        $meta = $this->em->getMetadataFactory()->getAllMetadata();
        foreach ($meta as $m) {
            foreach ($m->getAssociationMappings() as $columnName => $associationMapping) {
                if (str_contains($associationMapping['targetEntity'], ThreeSixtyView::class)) {
                    $entities[] = [
                        "columnName" => $columnName,
                        "entity" => $m->getName(),
                    ];
                }
            }
        }

        return $entities;
    }


}