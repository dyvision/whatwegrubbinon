<?php
header('content-type: application/json');
include('../lib/wwgo.php');

use wwgo\auth;
use wwgo\recipe;
use wwgo\misc;

$apikey = $_SERVER['PHP_AUTH_USER'];
$apisecret = $_SERVER['PHP_AUTH_PW'];

$auth = new auth();
$auth->api_verify($apikey, $apisecret);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $results = [];
    $post = json_decode(file_get_contents('php://input'), true);
    if ($post == null) {
        $post = $_POST;
    }
    $filter = new misc();
    $food = new recipe($apikey);
    $filter_results = json_decode($filter->scan_content(null, $post['url']), true);
    array_push($results,$filter_results);
    if ($_POST != null) {
        foreach ($filter_results as $key) {
            if (in_array($key, array('POSSIBLE', 'LIKELY', 'VERY_LIKELY'))) {
                header('location: ../profile?error=6');
            }
        }
    } else {
        foreach (array_values($filter_results) as $key) {
            if (in_array($key, array('POSSIBLE', 'LIKELY', 'VERY_LIKELY'))) {
                $result['error'] = 'detected ' . $key . ' themes';
                array_push($results, $result);
            }
        }
        exit(json_encode($results));
    }
    #print_r($food->create($post['url']));
    if ($_POST != null) {
        header('location: ../profile');
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $food = new recipe($apikey);
    print_r($food->get($_GET['rid']));
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $food = new recipe($apikey);
    print_r($food->delete($_GET['rid']));
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $post = json_decode(file_get_contents('php://input'), true);
    if ($post == null) {
        $post = $_POST;
    }
    $food = new recipe($apikey);
    print_r($food->add_user($post['rid']));
} else {
    header('HTTP/1.1 403 Not Supported');
    exit("Method not supported");
}
