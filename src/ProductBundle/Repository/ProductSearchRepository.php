<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CurrencyBundle\Entity\ExchangeRate;
use App\CurrencyBundle\Service\UserCurrencyService;
use App\ECommerceBundle\Entity\OrderHasProductPrice;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductSearch;
use App\ProductBundle\Entity\SubAttribute;
use App\ProductBundle\Model\ProductSearchModel;
use App\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSearchRepository extends BaseRepository
{
    private TranslatorInterface $translator;
    private UserCurrencyService $userCurrencyService;
    private UserService $userService;

    public function __construct(ManagerRegistry $registry, TranslatorInterface $translator, UserCurrencyService $userCurrencyService, UserService $userService)
    {
        parent::__construct($registry, ProductSearch::class, "ps");
        $this->translator = $translator;
        $this->userCurrencyService = $userCurrencyService;
        $this->userService = $userService;
    }

    public function deleteByProduct(Product $product)
    {
        return $this->createQueryBuilder("ps")
            ->delete()
            ->andWhere("ps.product = :productId")
            ->setParameter("productId", $product->getId())
            ->getQuery()->execute();
    }

    public function deleteUnUsedProducts()
    {
        $ids = [];
        $productsIds = $this->createQueryBuilder('ps')
            ->select('p.id')
            ->leftJoin("ps.product", "p")
            ->orWhere("p.publish = 0")
            ->orWhere("p.deleted IS NOT NULL")
            ->getQuery()->getResult();

        foreach ($productsIds as $productsId) {
            $ids[] = $productsId['id'];
        }

        return $this->createQueryBuilder("ps")
            ->delete()
            ->where('ps.product in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()->execute();
    }

    public function getExpiredOffers($limit = 500): array
    {
        return $this->createQueryBuilder("ps")
            ->andWhere("ps.offerExpiryDate < CURRENT_DATE()")
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getRelatedProducts(Product $product, $limit = 6): array
    {
        $search = new \stdClass();
        $search->ordr = 0;
        $search->notId = $product->getId();
        $search->category = $product->getCategory()->getId();

        return $this->filter($search, false, 0, $limit);
    }


    /**
     * Get all categories has product with filter parameters
     * @param \stdClass|null $search
     * @return array
     */
    public function getCategoriesByFilter(\stdClass $search = null): array
    {
        $qb = $this->_em->createQueryBuilder();
        $statement = $qb->from(Category::class, "cat")
            ->select("cat")
            ->addSelect("count(DISTINCT ps.product) noOfProducts")
            ->leftJoin(Product::class, 'p', "WITH", "p.category = cat.id")
            ->leftJoin(ProductSearch::class, "ps", "WITH", "p.id=ps.product")
            ->leftJoin('p.productHasCollections', 'phc')
            ->leftJoin("phc.collection", "c")
            ->leftJoin('p.productHasOccasions', 'pho')
            ->leftJoin("pho.occasion", "oc");
        $this->addCurrencyColumnInSelectStatement($statement, $search);

        if ($search != null) {
            $this->filterWhereClause($statement, $search);
        }

        $statement->groupBy("cat.id")
            ->andHaving("noOfProducts > 0");

        $results = $statement->getQuery()->execute();
        $return = [];
        foreach ($results as $row) {
            $obj = $row[0];
            $obj->noOfProductss = $row['noOfProducts'];
            $return[] = $obj;
        }

        return $return;
    }

    /**
     * Get all brands has product with filter parameters
     * @param \stdClass|null $search
     * @return array
     */
    public function getBrandsByFilter(\stdClass $search = null): array
    {
        $qb = $this->_em->createQueryBuilder();
        $statement = $qb->from(Brand::class, "b")
            ->select("b")
            ->addSelect("count(DISTINCT ps.product) noOfProducts")
            ->leftJoin(Product::class, 'p', "WITH", "p.brand = b.id")
            ->leftJoin(ProductSearch::class, "ps", "WITH", "p.id=ps.product")
            ->leftJoin('p.productHasCollections', 'phc')
            ->leftJoin("phc.collection", "c")
            ->leftJoin('p.productHasOccasions', 'pho')
            ->leftJoin("p.category", 'cat')
            ->leftJoin("pho.occasion", "oc");
        $this->addCurrencyColumnInSelectStatement($statement, $search);


        if ($search != null) {
            $currentSearch = clone $search;
            if (isset($currentSearch->brands)) {
                unset($currentSearch->brands);
            }
            if (isset($currentSearch->brand)) {
                unset($currentSearch->brand);
            }

            $this->filterWhereClause($statement, $currentSearch);
        }

        $statement->groupBy("b.id")
            ->andHaving("noOfProducts > 0");

        $results = $statement->getQuery()->execute();
        $return = [];
        foreach ($results as $row) {
            $obj = $row[0];
            $obj->noOfProductss = $row['noOfProducts'];
            $return[] = $obj;
        }

        return $return;
    }

    /**
     * Get all brands has product with category and filter parameters
     * @param Category $category
     * @param \stdClass|null $search
     * @return array
     */
    public function getSpecsByCategory(Category $category, \stdClass $search = null): array
    {
        $choicesSpecs = $this->getChoicesSpecsByCategory($category, $search);
        $textSpecs = $this->getTextSpecsByCategory($category);

        $allSpecs = array_merge($choicesSpecs, $textSpecs);
        $sortArray = [];
        foreach ($allSpecs as $choice) {
            $sortArray[] = [
                "attribute" => $choice,
                "sort" => $choice->getTarteb() == null ? 100 : $choice->getTarteb(),
            ];
        }
        uasort($sortArray, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        $return = [];
        foreach ($sortArray as $sortedNod) {
            $return[] = $sortedNod['attribute'];
        }

        return $return;
    }

    private function getTextSpecsByCategory(Category $category)
    {
        $statement2 = $this->_em->createQueryBuilder()
            ->select("a")
            ->from(Attribute::class, "a")
            ->andWhere("a.type IN (:types)")
            ->andWhere("a.search = :search")
            ->andWhere("a.category = :categoryId")
            ->andWhere("a.deleted IS NULL")
            ->setParameter("search", true)
            ->setParameter("categoryId", $category->getId())
            ->setParameter("types", [Attribute::TYPE_NUMBER, Attribute::TYPE_TEXT]);

        return $statement2->getQuery()->execute();
    }

    private function getChoicesSpecsByCategory(Category $category, \stdClass $search = null): array
    {
        $qb = $this->_em->createQueryBuilder();
        $statement = $qb
            ->select("sa, a")
            ->addSelect("count(DISTINCT ps.product) noOfProducts")
            ->addSelect("-a.tarteb AS HIDDEN inverseTarteb")
            ->from(SubAttribute::class, "sa")
            ->leftJoin("sa.attribute", 'a')
            ->leftJoin("sa.productHasAttributes", 'pha')
            ->leftJoin(ProductSearch::class, 'ps', "WITH", "pha.product=ps.product")
            ->leftJoin("ps.product", 'p')
            ->leftJoin('p.productHasCollections', 'phc')
            ->leftJoin("phc.collection", "c")
            ->leftJoin('p.productHasOccasions', 'pho')
            ->leftJoin("pho.occasion", "oc");

        if ($search != null) {
            $currentSearch = clone $search;
            if (isset($currentSearch->categories)) {
                unset($currentSearch->categories);
            }
            if (isset($currentSearch->category)) {
                unset($currentSearch->category);
            }
            if (isset($currentSearch->specs)) {
                unset($currentSearch->specs);
            }
            $this->addCurrencyColumnInSelectStatement($statement, $search);

            $this->filterWhereClause($statement, $currentSearch);
        }

        $statement
            ->andWhere("a.search = :search")
            ->andWhere("a.category = :categoryId")
            ->andWhere("a.deleted IS NULL")
            ->andWhere("sa.deleted IS NULL")
            ->setParameter("search", true)
            ->setParameter("categoryId", $category->getId())
            ->orderBy("inverseTarteb", "DESC")
            ->addOrderBy("a.id", "ASC")
            ->addOrderBy("sa.title+0", "ASC")
            ->groupBy("sa.id")
            ->andHaving("noOfProducts > 0");

        $results = $statement->getQuery()->execute();

        $returnChoices = [];
        foreach ($results as $row) {
            $subAttribute = $row[0];
            $subAttribute->noOfProducts = $row['noOfProducts'];
            $attribute = $subAttribute->getAttribute();
            $attribute->subSpecs[] = $subAttribute;
            $returnChoices[$attribute->getId()] = $attribute;
        }

        return $returnChoices;
    }

    protected function getStatement(\stdClass $search): QueryBuilder
    {
        $statement = $this->createQueryBuilder("ps")
            ->addSelect("IDENTITY(ps.product) AS productId")
            ->addSelect("cat.title AS categoryTitle")
            ->addSelect("b.title AS brandTitle")
            ->addSelect("p")
            ->addSelect("pTrans")
            ->addSelect("ps")
            ->addSelect("pd")
            ->addSelect("seo")
            ->addSelect("seoTrans")
            ->addSelect("post")
            ->leftJoin("ps.product", "p")
            ->leftJoin('p.productHasCollections', 'phc')
            ->leftJoin("phc.collection", "c")
            ->leftJoin('p.productHasOccasions', 'pho')
            ->leftJoin("pho.occasion", "oc")
            ->leftJoin("p.details", "pd")
            ->leftJoin("p.seo", "seo")
            ->leftJoin("seo.translations", "seoTrans")
            ->leftJoin("p.translations", "pTrans")
            ->leftJoin("p.post", "post")
            ->leftJoin("p.category", 'cat')
            ->leftJoin("p.brand", 'b')
        ;
        $this->addCurrencyColumnInSelectStatement($statement, $search);

        if (isset($search->currentUserId) and Validate::not_null($search->currentUserId)) {
            $statement->leftJoin('p.favorites', 'pf', "WITH", "pf.user=:currentUserId");
            $statement->addSelect("IDENTITY(pf.product) hasFavorite");
            $statement->setParameter("currentUserId", $search->currentUserId);
        }
        if (isset($search->bestSeller) and $search->bestSeller === true) {
            $statement
                ->leftJoin("p.prices", "pp")
                ->leftJoin(OrderHasProductPrice::class, "ohpp", "WITH", "ohpp.productPrice = pp.id")
                ->leftJoin("ohpp.order", "o");
        }

        return $statement;
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $session = new Session();
//        if ($session->has("order-rand-number")) {
//            $orderRandNumber = $session->get("order-rand-number");
//        } else {
//            $orderRandNumber = rand(0, 100);
//            $session->set("order-rand-number", $orderRandNumber);
//        }

        $statement->addOrderBy("ps.hasStock", "DESC");
        $sortSQL = [
            'ps.recommendedSort',
            'ps.product',
            $this->getPriceColumn($search),
            "ps.promotionPercentage"
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        // Base filters - always apply these for frontend display
        $statement->andWhere('p.publish = 1');
        $statement->andWhere('p.deleted IS NULL');
        
        //        if (isset($search->string) and Validate::not_null($search->string)) {
        //            $statement->addSelect("MATCH_AGAINST (ps.normalizedTxt, :searchTerm ) as score");
        //            $statement->andWhere("MATCH_AGAINST(ps.normalizedTxt, :searchTerm) > 0.5");
        //
        //            $normalizeStr = SearchText::normalizeKeyword($search->string);
        //            if (!isset($search->autocomplete) or $search->autocomplete == false) {
        //                // insert in searched keywords
        //                $this->getEntityManager()->getRepository(SearchKeyword::class)->insert($normalizeStr);
        //            }
        //
        //            $statement->setParameter('searchTerm', trim($normalizeStr));
        //            if ($search->count == false) {
        //                $statement->addOrderBy('score', 'desc');
        //            }
        //        }
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('ps.normalizedTxt LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->featured) and $search->featured != "") {
            $statement->andWhere('ps.featured = :featured');
            $statement->setParameter('featured', $search->featured);
        }

        if (isset($search->currentUserId) and Validate::not_null($search->currentUserId) and
            isset($search->favoriteUserId) and Validate::not_null($search->favoriteUserId)
        ) {
            $statement->andWhere('pf.user = :favoriteUserId');
            $statement->setParameter('favoriteUserId', $search->favoriteUserId);
        }

        if (isset($search->id) and $search->id != "") {
            $statement->andWhere('ps.product = :id');
            $statement->setParameter('id', $search->id);
        }

        if (isset($search->notId) and $search->notId != "") {
            $statement->andWhere('ps.product <> :id');
            $statement->setParameter('id', $search->notId);
        }

        if (isset($search->bestSeller) and $search->bestSeller === true) {
            $statement->andWhere("o.state = :state")
                ->setParameter("state", OrderStatusEnum::SUCCESS->value)
                ->orderBy("o.created", "DESC")
                ->addOrderBy("ps.product", "DESC");
        }

        if (isset($search->minPrice) and $search->minPrice != "") {
            $priceColumnSql = $this->getPriceColumn($search);
            $statement->andWhere("($priceColumnSql) >= :minPrice");
            $statement->setParameter('minPrice', $search->minPrice);
        }

        if (isset($search->maxPrice) and $search->maxPrice != "") {
            $priceColumnSql = $this->getPriceColumn($search, "ps.maxSellPrice");
            $statement->andWhere("($priceColumnSql)<= :maxPrice");
            $statement->setParameter('maxPrice', $search->maxPrice);
        }

        if ((isset($search->priceFrom) and $search->priceFrom != "") and (isset($search->priceTo) and $search->priceTo != "")) {
            $minPriceColumnSql = $this->getPriceColumn($search);
            $maxPriceColumnSql = $this->getPriceColumn($search, "ps.maxSellPrice");

            $statement->andWhere("($minPriceColumnSql) >= :priceFrom AND ($minPriceColumnSql) <=:priceTo
            OR
            ($maxPriceColumnSql) >= :priceFrom AND ($maxPriceColumnSql) <=:priceTo");
            $statement->setParameter('priceFrom', $search->priceFrom);
            $statement->setParameter('priceTo', $search->priceTo);
        }

        if (isset($search->notIds) and is_array($search->notIds) and count($search->notIds) > 0) {
            $statement->andWhere('ps.product NOT IN (:notIds)');
            $statement->setParameter('notIds', $search->notIds);
        }

        if (isset($search->premium) and $search->premium != "") {
            $statement->andWhere('ps.premium = :premium');
            $statement->setParameter('premium', $search->premium);
        }

        if (isset($search->category) and is_integer($search->category)) {
            $statement->andWhere('ps.category = :category');
            $statement->setParameter('category', $search->category);
        }

        if (isset($search->collection) and is_integer($search->collection)) {
            $statement->andWhere('c.id = :collection');
            $statement->setParameter('collection', $search->collection);
        }
        if (isset($search->occasion) and is_integer($search->occasion)) {
            $statement->andWhere('oc.id = :occasion');
            $statement->setParameter('occasion', $search->occasion);
        }

        if (isset($search->ids) and is_array($search->ids) and count($search->ids) > 0) {
            $statement->andWhere('ps.product in (:ids)');
            $statement->setParameter('ids', $search->ids);
        }

        if (isset($search->brands) and is_array($search->brands) and count($search->brands) > 0) {
            $statement->andWhere('ps.brand in (:brands)');
            $statement->setParameter('brands', $search->brands);
        }

        if (isset($search->brand) and is_numeric($search->brand)) {
            $statement->andWhere('ps.brand = :brand');
            $statement->setParameter('brand', $search->brand);
        }


        if (isset($search->categories) and is_array($search->categories) and count($search->categories) > 0) {
            $statement->andWhere('ps.category IN (:categories)');
            $statement->setParameter('categories', $search->categories);
        }

        if (isset($search->categoryLevelOne) and is_numeric($search->categoryLevelOne)) {
            $statement->andWhere('cat.levelOne = :categoryLevelOne');
            $statement->setParameter('categoryLevelOne', $search->categoryLevelOne);
        }
        if (isset($search->category) and is_numeric($search->category)) {
            $statement->andWhere('cat.id = :category');
            $statement->setParameter('category', $search->category);
        }

        if (isset($search->offer) and $search->offer == true) {
            $statement->andWhere('ps.hasOffer = :hasOffer');
            $statement->setParameter('hasOffer', true);
        }
        if (isset($search->hasStock) and $search->hasStock != "") {
            $statement->andWhere('ps.hasStock = :hasStock');
            $statement->setParameter('hasStock', $search->hasStock);
        }

        if (isset($search->newArrival) and (is_bool($search->newArrival) or in_array($search->newArrival, [0, 1]))) {
            $statement->andWhere('ps.newArrival = :newArrival');
            $statement->setParameter('newArrival', $search->newArrival);
        }


        if (isset($search->specs) and is_array($search->specs) and count($search->specs) > 0) {

            foreach ($search->specs as $specsId => $subSpecs) {
                $subSpecsWhere = false;
                $subSpecsClause = [];


                if (is_array($subSpecs)) { // Search in choices
                    foreach ($subSpecs as $subSpecsId) {
                        $subSpecsWhere = ($subSpecsWhere === false) ? " OR " : "";
                        $subSpecsClause[] = "JSON_CONTAINS(ps.specs, :subAttrId" . $subSpecsId . ", '$.attr_" . $specsId . "') = 1";
                        $statement->setParameter('subAttrId' . $subSpecsId, $subSpecsId);
                    }
                    $statement->andWhere("(" . implode("OR ", $subSpecsClause) . ")");
                } else { // Search in text and Number
                    $statement->andWhere("JSON_CONTAINS(ps.specs, :value" . $specsId . ", '$.attr_" . $specsId . "') = 1");
                    $statement->setParameter('value' . $specsId, $subSpecs);
                }
            }
        }


    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null): array|int
    {
        $search->count = $count;

        if ($this->userService->getUser() instanceof User) {
            $search->currentUserId = $this->userService->getUser()->getId();
        }

        $statement = $this->getStatement($search);
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement, "product");
        }
        $statement->groupBy('ps.product');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);
        $statementResult = $statement->getQuery()->execute();

        $products = [];
        if (is_array($statementResult)) {
            foreach ($statementResult as $row) {
                if (is_array($row)) {
                    $productSearch = $row['0'];
                } else {
                    $productSearch = $row;
                }
                $hasFavorite = (array_key_exists("hasFavorite", $row) and $row['hasFavorite'] != null) ? true : false;
                $productId = $row['productId'];
//                $avgRate = $row['avgRate'];
                $categoryTitle = $row['categoryTitle'];
                $brandTitle = $row['brandTitle'];


                $locale = $this->translator->getLocale();
                $products[] = new ProductSearchModel(
                    productSearch: $productSearch,
                    locale: $locale,
                    productId: $productId,
                    categoryTitle: $categoryTitle,
                    brandTitle: $brandTitle,
                    hasFavorite: $hasFavorite,
                    rate: null);
            }
        }

        return $products;
    }

    public function getMaxAndMinPricesForFilter($search): array
    {
        $statement = $this->getStatement($search);
        $search->count = true;

        $currentSearch = clone $search;

        if (isset($currentSearch->minPrice)) {
            unset($currentSearch->minPrice);
        }
        if (isset($currentSearch->priceFrom)) {
            unset($currentSearch->priceFrom);
        }

        if (isset($currentSearch->maxPrice)) {
            unset($currentSearch->maxPrice);
        }
        if (isset($currentSearch->priceTo)) {
            unset($currentSearch->priceTo);
        }


        $this->filterWhereClause($statement, $currentSearch);
        $statement->select("LEAST(MIN(ps.minSellPrice), MIN(ps.minSellPrice)) AS minPrice, GREATEST(MAX(ps.maxSellPrice), MAX(ps.maxSellPrice)) AS maxPrice");
        $statement->setMaxResults(1);

        $results = $statement->getQuery()->getArrayResult();
        $prices = [];
        if (is_array($results) and count($results) > 0) {
            $prices["min_price"] = $results[0]["minPrice"];
            $prices["max_price"] = $results[0]["maxPrice"];
        }

        return $prices;
    }

    public function makeImageEmptyByImage(Image $image)
    {
        return $this->createQueryBuilder("ps")
            ->update()
            ->set("ps.mainImage", "null")
            ->andWhere("ps.mainImage = :id")
            ->setParameter("id", $image->getId())
            ->getQuery()
            ->execute();
    }


    private function getPriceColumn(\stdClass $search = null, string $columnName = "ps.minSellPrice"): string
    {
        return $columnName . " * IFNULL(exr.ratio, 1)";
    }

    public function maxPriceForFilter($search): float
    {
        $statement = $this->getStatement($search);
        $search->count = true;

        $currentSearch = clone $search;
        if (isset($currentSearch->minPrice)) {
            unset($currentSearch->minPrice);
        }
        if (isset($currentSearch->priceFrom)) {
            unset($currentSearch->priceFrom);
        }
        if (isset($currentSearch->maxPrice)) {
            unset($currentSearch->maxPrice);
        }
        if (isset($currentSearch->priceTo)) {
            unset($currentSearch->priceTo);
        }
        $priceColumnSql = $this->getPriceColumn($search, "ps.maxSellPrice");
        $this->filterWhereClause($statement, $currentSearch);
        $statement->select("MAX($priceColumnSql)");
        $statement->setMaxResults(1);

        $count = $statement->getQuery()->getOneOrNullResult();
        if (is_array($count) and count($count) > 0) {
            return ceil(reset($count));
        }

        return 0;
    }

    private function addCurrencyColumnInSelectStatement(QueryBuilder $statement, \stdClass $search = null): void
    {
        $priceColumnSql = $this->getPriceColumn($search);
        $statement->addSelect($priceColumnSql . " AS calculatedPrice");

        $statement->leftJoin(ExchangeRate::class, "exr", "WITH",
            "exr.sourceCurrency=ps.currency AND exr.targetCurrency =:userCurrencyId");

        $statement->setParameter("userCurrencyId", $this->userCurrencyService->getCurrency()->getId());
    }
}
