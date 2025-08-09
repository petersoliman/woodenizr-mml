<?php
$servername = "localhost";
$username = "root";
$password = "root";
$db = "test";

try {
    $JpConn = new \PDO("mysql:host=$servername;dbname=" . $db, $username, $password);
    $JpConn->exec("set names utf8mb4");

    // set the PDO error mode to exception
    $JpConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    exit("Connection failed: " . $e->getMessage());
}

$servername = "localhost";
$username = "root";
$password = "root";
$db = "woodenizr";

try {
    $woodenizerConn = new \PDO("mysql:host=$servername;dbname=" . $db, $username, $password);
    $woodenizerConn->exec("set names utf8mb4");

    // set the PDO error mode to exception
    $woodenizerConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    exit("Connection failed: " . $e->getMessage());
}

function getList($query, $conn = null): bool|array
{
    if ($conn == null) {
        global $JpConn;
        $conn = $JpConn;
    }
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOneColumn($sql, $conn = null): mixed
{
    if ($conn == null) {
        global $JpConn;
        $conn = $JpConn;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function escape($str)
{
    return str_replace(["\\", "'", '"'], ["\\\\", "\'", '\"'], $str);
}