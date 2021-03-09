<?php
header('content-type: application/json');
include('../lib/wwgo.php');

use wwgo\auth;
use wwgo\recommendation;

$apikey = $_SERVER['PHP_AUTH_USER'];
$apisecret = $_SERVER['PHP_AUTH_PW'];

$auth = new auth();
$auth->api_verify($apikey, $apisecret);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post = json_decode(file_get_contents('php://input'), true);
    if ($post == null) {
        $post = $_POST;
    }
    $rec = new recommendation($apikey);
    print_r($rec->send($post['rid'], $post['email']));
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $rec = new recommendation($apikey);
    print_r($rec->generate($_GET['rid']));
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $rec = new recommendation($apikey);
    print_r($rec->delete($_GET['tid']));
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $post = json_decode(file_get_contents('php://input'), true);
    if ($post == null) {
        $post = $_POST;
    }
    $rec = new recommendation($apikey);
    print_r($rec->create($post['id'], $post['tz'], $post['type']));
} else {
    header('HTTP/1.1 403 Not Supported');
    exit("Method not supported");
}
