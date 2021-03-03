<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;
use wwgo\visual;

if (isset($_COOKIE['id']) and isset($_COOKIE['guid']) and isset($_COOKIE['refresh_token'])) {
    $auth = new auth();
    $token = json_decode($auth->authenticate($_COOKIE['refresh_token'], 'refresh_token'), true);
    $verify = json_decode($auth->verify($token['access_token']), true);
    $user = new user($token['access_token'], $_COOKIE['id'], $_COOKIE['guid'], null);
} else {
    header('location: home.php');
}
$user->pull();
$profile = json_decode($user->get(), true);

?>

<head>
    <script src="lib/wwgo.js"></script>
    <link href='style/style.css' rel='stylesheet'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='shortcut icon' type='image/png' href='style/wwgologo.png' />
    <title>WWGO</title>
</head>

<?php
$build = new visual();
echo $build->header();
?>

<body>
    <?php echo $header; ?>
    <div class='banner' style="background:url(style/burger.jpg);display: table; #position: relative; overflow: hidden;">
        <div style="#position: absolute; #top: 50%;display: table-cell; vertical-align: middle;">
            <div style="padding:0 3%;#position: relative; #top: -50%">
                <div class='block'>
                    <image class='profile' src=<?php echo "'" . $profile['image'] . "'"; ?>></image>
                    <div class='blockbody'>
                        <h3><?php echo $profile['email'] ?></h3>
                        <h3><?php echo $profile['fullname'] ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>