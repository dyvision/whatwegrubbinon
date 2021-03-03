<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;

if (isset($_COOKIE['id']) and isset($_COOKIE['guid'])) {

    header('profile.php');
} else {
    $auth = new auth();
    $token = json_decode($auth->authenticate($_GET['code'], 'authorization_code'), true);
    $verify = json_decode($auth->verify($token['access_token']), true);
    if ($verify['guid'] != '') {
        $user = new user($token['access_token'], $verify['id'], $verify['guid'], null);
        $user->pull();
    } else {
        $user = new user($token['access_token'], $verify['id'], null, $token['refresh_token']);
        $user->create();
        $user->pull();
    }
    $user->login();

    header('profile.php');
}
