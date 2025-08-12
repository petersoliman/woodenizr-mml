<?php
global $woodenizerConn, $JpConn;

include_once 'db_connection.php';
include_once 'slug.php';
$arabicLanguageId = getOneColumn("SELECT id FROM `language` WHERE locale='ar';", $woodenizerConn); //todo: get value from db
$sqlQueries = [
    "START TRANSACTION;"
];


$brandNewId = 1;
$seoNewId = getOneColumn("SELECT id FROM `seo` ORDER BY id DESC LIMIT 1;", $woodenizerConn) + 1;

$postNewId = getOneColumn("SELECT id FROM `post` ORDER BY id DESC LIMIT 1;", $woodenizerConn) + 1;
$imageNewId = (getOneColumn("SELECT id FROM `image` ORDER BY id DESC LIMIT 1;", $woodenizerConn) ?? 0) + 1;
$seoBaseRouteId = getOneColumn("SELECT id FROM `seo_base_route` WHERE entity_name LIKE '%\Brand';", $woodenizerConn);
$sql = "SELECT b.*, st.title arabic, p.id AS postId,p.content, pt.content AS arabicContent FROM brand b LEFT JOIN brand_translations st ON b.id = st.translatable_id LEFT JOIN post p ON p.id=b.post_id LEFT JOIN post_translations pt ON pt.translatable_id=p.id WHERE b.deleted=0 ORDER BY `b`.`id` ASC;";
$brands = getList($sql, $JpConn);

foreach ($brands as $brand) {
    $id = $brand['id'];
    $postId = $brand['postId'];
    $title = escape($brand['title']);
    $titleArabic = escape($brand['arabic']);
    $content = ($brand['content'] == null or $brand['content'] == "[]") ? "{}" : $brand['content'];
    $arabicContent = ($brand['arabicContent'] == null or $brand['arabicContent'] == "[]") ? "{}" : $brand['arabicContent'];
    $content = escape($content);
    $arabicContent = escape($arabicContent);

    $created_at = date("Y-m-d H:i:s");
    $slugEnglish = Slug::sanitize($title);
    $slugArray = ['oulee', 'st-vitali', 'honeywell'];
    $slugEnglish = (in_array($slugEnglish, $slugArray)) ? ($slugEnglish . "-" . $brandNewId) : $slugEnglish;
    $slugArabic = Slug::sanitize($titleArabic);

    $sqlQueries[] = "INSERT INTO `post` (`id`, `content`) VALUES ($postNewId, '" . $content . "');";
    $sqlQueries[] = "INSERT INTO `post_translations` (`translatable_id`, `language_id`, `content`) VALUES ($postNewId, $arabicLanguageId, '" . $arabicContent . "');";
    $postImages = getList("SELECT i.id, i.name, i.image_type, i.base_path, i.alt, i.width, i.height, i.size FROM `post_image` pi LEFT JOIN image i ON i.id=pi.image_id WHERE pi.post_id={$postId};", $JpConn);
    foreach ($postImages as $postImage) {
        $imageId = $postImage['id'];
        $imageName = $postImage['name'];
        if ($imageName == null) continue;
        $imageType = ($postImage['image_type'] > 0) ? $postImage['image_type'] : "null";
        $imageBasePath = $postImage['base_path'];
        $imageAlt = $postImage['alt'];
        $imageWidth = $postImage['width'];
        $imageHeight = $postImage['height'];
        $imageSize = $postImage['size'];

        $sqlQueries[] = "INSERT INTO `image` (`id`, `name`, `alt`, `base_path`, `image_type`, `width`, `height`, `size`, `created`) VALUES ($imageNewId, '" . $imageName . "', '" . $imageAlt . "', '" . $imageBasePath . "', " . $imageType . ", " . $imageWidth . ", " . $imageHeight . "," . $imageSize . ", NOW());";
        $sqlQueries[] = "INSERT INTO `post_image` (`post_id`, `image_id`) VALUE ({$postNewId}, {$imageNewId});";
        $imageNewId++;

    }

    $sqlQueries[] = "INSERT INTO `seo` (`id`, `seo_base_route_id`, `title`, `slug`, `meta_description`, `focus_keyword`, `meta_keyword`, `meta_tags`, `state`, `last_modified`, `deleted`) VALUES ($seoNewId, $seoBaseRouteId, '" . $title . "', '" . $slugEnglish . "', NULL, NULL, NULL, NULL, 1, 'NOW()', 0);";
    $sqlQueries[] = "INSERT INTO `seo_translations` (`translatable_id`, `language_id`, `title`, `slug`, `meta_description`, `focus_keyword`, `meta_keyword`, `meta_tags`, `state`) VALUES ($seoNewId, $arabicLanguageId, '" . $titleArabic . "', '" . $slugArabic . "', NULL, NULL, NULL, NULL, 1);";
    $sqlQueries[] = "INSERT INTO `brand` (`id`, `seo_id`, `post_id`, `jp_id`, `title`, `publish`, `featured`, `tarteb`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`) VALUES (" . $brandNewId . ", '" . $seoNewId . "', '" . $postNewId . "', '" . $id . "', '" . $title . "', '1', '0', '1', NULL, NULL, 'NOW()', 'System', 'NOW()', 'System');";
    $sqlQueries[] = "INSERT INTO `brand_translations` (`translatable_id`, `language_id`, `title`) VALUES ('" . $brandNewId . "', '" . $arabicLanguageId . "', '" . $titleArabic . "');";

    $brandNewId++;
    $seoNewId++;
    $postNewId++;
}


$sqlQueries[] = "COMMIT;";
echo implode("\n", $sqlQueries);