<?php
global $woodenizerConn, $JpConn;

include_once 'db_connection.php';
include_once 'slug.php';
$arabicLanguageId = getOneColumn("SELECT id FROM `language` WHERE locale='ar';", $woodenizerConn);
$sqlQueries = [
    "START TRANSACTION;"
];


$productNewId = getOneColumn("SELECT id FROM `product` ORDER BY id DESC LIMIT 1;", $woodenizerConn) + 1;
$categoryId = getOneColumn("SELECT id FROM `category` ORDER BY id DESC LIMIT 1;", $woodenizerConn);
$currencyId = getOneColumn("SELECT id FROM `currency` ORDER BY id DESC LIMIT 1;", $woodenizerConn);
if ($categoryId == null) {
    exit("no category found");
}

$seoNewId = getOneColumn("SELECT id FROM `seo` ORDER BY id DESC LIMIT 1;", $woodenizerConn) + 1;

$postNewId = getOneColumn("SELECT id FROM `post` ORDER BY id DESC LIMIT 1;", $woodenizerConn) + 1;
$imageNewId = (getOneColumn("SELECT id FROM `image` ORDER BY id DESC LIMIT 1;", $woodenizerConn) ?? 0) + 1;
$seoBaseRouteId = getOneColumn("SELECT id FROM `seo_base_route` WHERE entity_name LIKE '%\Product';", $woodenizerConn);
$sql = "SELECT p.id, p.post_id, p.seo_id, s.meta_tags, s.meta_description, s.focus_keyword, s.meta_keyword, p.title, p.normalized_text, p.tags, p.publish, p.brand_obj_id, p.model_no, pt.title arabic, po.id AS postId,po.content, ptt.content AS arabicContent FROM product p LEFT JOIN product_translations pt ON p.id = pt.translatable_id LEFT JOIN post po ON po.id=p.post_id LEFT JOIN post_translations ptt ON ptt.translatable_id=po.id LEFT JOIN seo s ON s.id=p.seo_id WHERE p.deleted IS NULL AND p.seller_id IN (15826, 15292, 13224) ORDER BY `p`.`brand_obj_id` DESC;";
$products = getList($sql, $JpConn);

foreach ($products as $product) {
    $id = $product['id'];
    $postId = $product['postId'];
    $title = escape($product['title']);
    $titleArabic = escape($product['arabic']);
    $jpBrandId = $product['brand_obj_id'];
    $brandId = getOneColumn("SELECT id FROM `brand` WHERE jp_id={$jpBrandId};", $woodenizerConn) ?? "null";
    $brandId = ($brandId == false) ? "null" : $brandId;

    $published = $product['publish'];
    $sku = escape($product['model_no']);
    $normalizedText = escape($product['normalized_text']);
    $searchTerms = escape($product['tags']);

    $seo_search_terms = ["Just Piece", "JustPiece", 'justpiece', 'Justpiece', 'just piece', 'Just piece'];
    $seo_replace_terms = ["Woodenizr", "Woodenizr", 'woodenizr', 'Woodenizr', 'woodenizr', 'Woodenizr'];
    $metaTags = escape(str_replace($seo_search_terms, $seo_replace_terms, $product['meta_tags']));
    $focusKeyword = escape(str_replace($seo_search_terms, $seo_replace_terms, $product['focus_keyword']));
    $metaDescription = escape(str_replace($seo_search_terms, $seo_replace_terms, $product['meta_description']));
    $metaKeyword = escape(str_replace($seo_search_terms, $seo_replace_terms, $product['meta_keyword']));

    $content = ($product['content'] == null or $product['content'] == "[]") ? "{}" : $product['content'];
    $arabicContent = ($product['arabicContent'] == null or $product['arabicContent'] == "[]") ? "{}" : $product['arabicContent'];
    $content = escape($content);
    $arabicContent = escape($arabicContent);
    $uuid = generateUUID();
    $created_at = date("Y-m-d H:i:s");
    $slugEnglish = Slug::sanitize($title);
    $slugArray = ['rust-oleum-painter’s-touch-355ml-gloss-ultra-cover-purple-spray', 'oak-fas-wood-30152-54', 'oak-fas-wood-120302-54', 'oak-fas-wood-120402-54', 'oak-fas-wood-60302-54', 'oak-fas-wood-30302-54'];
//    $slugEnglish = (in_array($slugEnglish, $slugArray)) ? ($slugEnglish . "-" . $productNewId) : $slugEnglish;
    $slugEnglish = ($slugEnglish . "-" . $productNewId);
    $slugArabic = Slug::sanitize($titleArabic);
    $mainImageId = "null";
    $sqlQueries[] = "INSERT INTO `post` (`id`, `content`) VALUES ($postNewId, '" . $content . "');";
    $sqlQueries[] = "INSERT INTO `post_translations` (`translatable_id`, `language_id`, `content`) VALUES ($postNewId, $arabicLanguageId, '" . $arabicContent . "');";
    $postImages = getList("SELECT i.id, i.name, i.image_type, i.base_path, i.alt, i.width, i.height, i.size FROM `post_image` pi LEFT JOIN image i ON i.id=pi.image_id WHERE pi.post_id={$postId};", $JpConn);
    foreach ($postImages as $postImage) {
        $imageId = $postImage['id'];
        $imageName = $postImage['name'];
        if ($imageName == null) continue;
        $imageType = ($postImage['image_type'] > 0) ? $postImage['image_type'] : "null";
        $imageBasePath = $postImage['base_path'];
        $imageAlt = escape($postImage['alt']);
        $imageWidth = $postImage['width'];
        $imageHeight = $postImage['height'];
        $imageSize = $postImage['size'];
        $mainImageId = ($mainImageId == "null" and $imageType == "1") ? $imageNewId : "null";

        $sqlQueries[] = "INSERT INTO `image` (`id`, `name`, `alt`, `base_path`, `image_type`, `width`, `height`, `size`, `created`) VALUES ($imageNewId, '" . $imageName . "', '" . $imageAlt . "', '" . $imageBasePath . "', " . $imageType . ", " . $imageWidth . ", " . $imageHeight . "," . $imageSize . ", NOW());";
        $sqlQueries[] = "INSERT INTO `post_image` (`post_id`, `image_id`) VALUE ({$postNewId}, {$imageNewId});";
        $imageNewId++;

    }


    $sqlQueries[] = "INSERT INTO `seo` (`id`, `seo_base_route_id`, `title`, `slug`, `meta_description`, `focus_keyword`, `meta_keyword`, `meta_tags`, `state`, `last_modified`, `deleted`) VALUES ($seoNewId, $seoBaseRouteId, '" . $title . "', '" . $slugEnglish . "', '" . $metaDescription . "', '" . $focusKeyword . "', '" . $metaKeyword . "', '" . $metaTags . "', 1, NOW(), 0);";
    $sqlQueries[] = "INSERT INTO `seo_translations` (`translatable_id`, `language_id`, `title`, `slug`, `meta_description`, `focus_keyword`, `meta_keyword`, `meta_tags`, `state`) VALUES ($seoNewId, $arabicLanguageId, '" . $titleArabic . "', '" . $slugArabic . "', NULL, NULL, NULL, NULL, 1);";

    $sqlQueries[] = "INSERT INTO `product` (`id`, `seo_id`, `post_id`, `category_id`, `currency_id`, `main_image_id`, `title`, `sku`, `tarteb`, `search_terms`, `normalized_text`, `featured`, `new_arrival`, `enable_variants`, `publish`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`, `uuid`, `brand_id`) VALUES ($productNewId, $seoNewId, $postNewId, $categoryId, $currencyId, $mainImageId, '" . $title . "', '" . $sku . "', NULL, '" . $searchTerms . "', '" . $normalizedText . "', '0', '0', '0', '" . $published . "', NULL, NULL, NOW(), 'System', NOW(), 'System', '" . $uuid . "', $brandId);";
    $sqlQueries[] = "INSERT INTO `product_details` (`product_id`, `augmented_reality_url`) VALUES ($productNewId, NULL);";

    $productPrices = getList("SELECT title, foreign_price, foreign_promotional_price, promotional_expiry_date, stock, weight FROM `product_price` WHERE product_id=$id AND deleted=0", $JpConn);
    foreach ($productPrices as $productPrice) {
        $priceTitle = escape($productPrice['title']) ?? "null";
        $price = $productPrice['foreign_price'];
        $pricePromotionalPrice = $productPrice['foreign_promotional_price'] ? ("'".$productPrice['foreign_promotional_price']."'") : "null";
        $pricePromotionalExpiryDate = $productPrice['promotional_expiry_date'] ? ("'".$productPrice['promotional_expiry_date']."'") : "null";
        $priceStock = $productPrice['stock'];
        $priceWeight = $productPrice['weight'];

//if($productNewId==782){
//    var_dump($price);
//    var_dump($pricePromotionalPrice);
//    var_dump($pricePromotionalExpiryDate);
//    die();
//}
        $sqlQueries[] = "INSERT INTO `product_price` (`product_id`, `currency_id`, `title`, `stock`, `unit_price`, `promotional_price`, `promotional_expiry_date`, `variant_option_ids`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`, `weight`) VALUES ($productNewId, $currencyId, '" . $priceTitle . "', '" . $priceStock . "', '" . $price . "', " . $pricePromotionalPrice . ", " . $pricePromotionalExpiryDate . ", NULL, NULL, NULL, NOW(), 'System', NOW(), 'System', '" . $priceWeight . "');";
    }
    $sqlQueries[] = "INSERT INTO `product_translations` (`translatable_id`, `language_id`, `title`) VALUES ('" . $productNewId . "', '" . $arabicLanguageId . "', '" . $titleArabic . "');";

    $productNewId++;
    $seoNewId++;
    $postNewId++;
}


$sqlQueries[] = "COMMIT;";
echo implode("\n", $sqlQueries);

function generateUUID(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}