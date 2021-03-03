<?php
include('../lib/wwgo.php');

use wwgo\auth;
use wwgo\food;

$apikey = $_SERVER['PHP_AUTH_USER'];
$apisecret = $_SERVER['PHP_AUTH_PW'];

$auth = new auth();
$auth->api_verify($apikey, $apisecret);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post = json_decode(file_get_contents('php://input'), true);
    if ($post == null) {
        $post = $_POST;
    }
    $food = new food($apikey);
    $food->create($post['name'], $post['image'], $post['url']);
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $food = new food($apikey);
    $food->get($_GET['rid']);
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $food = new food($apikey);
    $food->delete($_GET['rid']);
} else {
    header('HTTP/1.1 403 Not Supported');
    exit("Method not supported");
}
