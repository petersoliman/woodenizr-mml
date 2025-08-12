<?php
global $woodenizerConn, $JpConn;

include_once 'db_connection.php';

$baseImagePath = "https://justpiece.com/uploads/";
$sql = "SELECT base_path, name FROM image WHERE id > 77";
$images = getList($sql, $woodenizerConn);
$i = 0;
$numberOfImages = count($images);
foreach ($images as $image) {

    echo ($i + 1) . "/" . $numberOfImages . "\n";

    $base_path = $image['base_path'];
    $name = $image['name'];
    $newBasePath = $baseImagePath . $base_path . "/" . $name;

    $headers = get_headers($newBasePath);
    if (!str_contains($headers[0], '200')) {
        echo "this file not equal 200: $newBasePath \n";
        continue;
    }

    $imageContent = file_get_contents($newBasePath);
    $localPath = __DIR__ . "/../uploads/" . $base_path . "/";
    if (file_exists($localPath . $name)) {
        echo "this file is already exist: $newBasePath \n";
        continue;
    }
    if (empty($imageContent)) {
        echo "this file is empty: $newBasePath \n";
        continue;
    }


    if (!file_exists($localPath)) {
        mkdir($localPath, 0777, true);
    }
    $i++;
    file_put_contents($localPath . $name, $imageContent);
}

echo "download $i files";
