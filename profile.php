<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;

if (isset($_COOKIE['id']) and isset($_COOKIE['guid'])) {

    $auth = new auth();
    $token = json_decode($auth->authenticate($_GET['code'], 'authorization_code'), true);
    $verify = json_decode($auth->verify($token['access_token']), true);
    $user = new user($token['access_token'], $_COOKIE['id'], $_COOKIE['guid'], null);

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

}

$profile = json_decode($user->get(),true);

?>

<head>
    <script src="lib/wwgo.js"></script>
    <link href='style/style.css' rel='stylesheet'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
</head>

<?php
if (isset($_COOKIE['id'])) {
    $header = "<div id='navbar'>
    <h3 class='navbar-item'>What We Grubbin' On</h3>
    <h3 class='navbar-item'><a href='Food.php'>Food</a></h3>
    <h3 class='navbar-item'><a href='profile.php'>Profile</a></h3>
    <h3 class='navbar-item' onclick='logout();'><a>Logout</a></h3>
    </div>";
} else {
    $header = "<div id='navbar'>
<h3 class='navbar-item'>What We Grubbin' On</h3>
<h3 class='navbar-item'><a href='Food.php'>Food</a></h3>
    <h3 class='navbar-item'><a href='profile.php'>Profile</a></h3>
<h3 class='navbar-item'><a href='authorize.php'>Login with Google</a></h3>
</div>";
}
?>

<body>
    <?php echo $header; ?>
    <span class='spacer'></span>
    <div class='row'>
        <image src=<?php echo "'".$profile['image']."'";?>></image>
        <h3><?php echo $profile['email'] ?></h3>
        <h3><?php echo $profile['fullname'] ?></h3>
    </div>

</body>

<?php

