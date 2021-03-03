<?php
include('lib/wwgo.php');

use wwgo\auth;

$auth = New auth();
print_r($auth->authenticate($_GET['code'],'authorization_code'));