<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;

$auth = new auth();
$auth->authenticate($_GET['code'], 'authorization_code');
if (isset($_COOKIE['id'])) {
    $user = new user($_COOKIE['id'], $_COOKIE['guid']);
} else {
    $user = new user();
}
