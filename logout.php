<?php

include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;

if (isset($_COOKIE['id']) and isset($_COOKIE['guid']) and isset($_COOKIE['refresh_token'])) {
    $auth = new auth();
    $token = json_decode($auth->authenticate($_COOKIE['refresh_token'], 'refresh_token'), true);
    $verify = json_decode($auth->verify($token['access_token']), true);
    if ($verify['guid'] != '') {
        $user = new user($token['access_token'], $verify['id'], $verify['guid'], null);
        $user->logout();
        header('location: /');
    } else {
        header('location: authorize.php');
    }
} else {
    header('location: authorize.php');
}
