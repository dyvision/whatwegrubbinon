<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;

$auth = new auth();
$token = json_decode($auth->authenticate($_GET['code'], 'authorization_code'), true);
$verify = json_decode($auth->verify($token['access_token']), true);
if ($verify['guid'] != '') {
    $user = new user($verify['id'], $_COOKIE['guid']);
} else {
    $user = new user($verify['id'],null,$token['refresh_token']);
}
print_r($user->get());
