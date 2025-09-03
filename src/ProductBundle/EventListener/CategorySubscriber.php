<?php

namespace App\ProductBundle\EventListener;

use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\ProductRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class CategorySubscriber implements EventSubscriber
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;

    public function __construct(ProductRepository $productRepository, CategoryRepository $categoryRepository)
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }
    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->updateNoOfProducts($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->updateNoOfProducts($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->updateNoOfProducts($args);
    }

    private function updateNoOfProducts(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Product) {
            return;
        }
        $em = $args->getObjectManager();

        $category = $entity->getCategory();
        while ($category) {
            $noOfAllProducts = $this->updateNoOfAllProducts($category);
            $category->setNoOfProducts($noOfAllProducts);

            $noOfPublishedProducts = $this->updateNoOfPublishedProducts($category, $em);
            $category->setNoOfPublishProducts($noOfPublishedProducts);
            $em->persist($category);

            $category = $category->getParent();

        }
        $em->flush();

    }

    private function updateNoOfAllProducts(Category $category): int
    {
        // Count only products that meet the search criteria (have prices, are not deleted, etc.)
        $qb = $this->productRepository->createQueryBuilder('p');
        $qb->select('COUNT(DISTINCT p.id)')
           ->leftJoin('p.prices', 'pp')
           ->where('p.deleted IS NULL')
           ->andWhere('p.category IN (:categories)')
           ->andWhere('pp.id IS NOT NULL') // Must have at least one price
           ->setParameter('categories', explode(',', $category->getConcatIds()));

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function updateNoOfPublishedProducts(Category $category): int
    {
        $categoriesIds = $this->getPublishCategories($category);
        if (count($categoriesIds) == 0) {
            return 0;
        }
        
        // Count only products that meet the search criteria (have prices, are published, etc.)
        $qb = $this->productRepository->createQueryBuilder('p');
        $qb->select('COUNT(DISTINCT p.id)')
           ->leftJoin('p.prices', 'pp')
           ->where('p.deleted IS NULL')
           ->andWhere('p.publish = :publish')
           ->andWhere('p.category IN (:categories)')
           ->andWhere('pp.id IS NOT NULL') // Must have at least one price
           ->setParameter('publish', true)
           ->setParameter('categories', $categoriesIds);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function getPublishCategories(Category $category)
    {
        $categoriesIds = explode(',', $category->getConcatIds());

        $search = new \stdClass();
        $search->ids = $categoriesIds;
        $search->publish = true;
        $search->deleted = 0;
        $categories = $this->categoryRepository->filter($search);

        $newCategoriesIds = [];
        foreach ($categories as $category) {
            $newCategoriesIds[] = $category->getId();
        }
        if (count($newCategoriesIds) == 0) {
            return $categoriesIds;
        }

        return $newCategoriesIds;
    }
}
