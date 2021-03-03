<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;
use wwgo\visual;
use wwgo\food;

if (isset($_COOKIE['id']) and isset($_COOKIE['guid']) and isset($_COOKIE['refresh_token'])) {
    $auth = new auth();
    $token = json_decode($auth->authenticate($_COOKIE['refresh_token'], 'refresh_token'), true);
    $verify = json_decode($auth->verify($token['access_token']), true);
    $user = new user($token['access_token'], $_COOKIE['id'], $_COOKIE['guid'], null);
} else {
    header('location: home');
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
    <div class='banner' style="background:url(style/profile.jpg);">
    <span style='background:transparent' class='spacer'></span>
            <div style="width:100%;padding:0 3%;">
                <div class='block'>
                    <image class='profile' src=<?php echo "'" . $profile['image'] . "'"; ?>></image>
                    <div class='blockbody'>
                        <h3><?php echo $profile['fullname'] ?></h3>
                        <h3>Get Recommendations</h3>
                        <form>
                            <select name='frequency'>
                                <option value='all'>Every Meal</option>
                                <option value='night'>Every Night</option>
                                <option value='morning'>Every morning</option>
                                <option value='mondays'>Every Monday</option>
                                <option value='month'>Once a month</option>
                            </select>
                            <button>Update Frequency</button>
                        </form>
                        <button class='recipe-button'>Add A Recipe</button></br>
                        <span style='color:grey'>API Key: <?php echo $profile['id'] ?></span></br>
                        <span style='color:grey'>API Secret:<?php echo $profile['guid'] ?></span>

                    </div>
                </div>
                <div class='bigblock'>
                    <h1 style='margin: 1%;font-size: 60;color: white;text-shadow: 0px 4px 6px #00000091;'>Recipes</h1>
                    <?php
                    $recipe = new food($_COOKIE['id']);
                    $recipes = json_decode($recipe->get(), true);
                    foreach ($recipes as $food) { ?>
                        <div class='innerblock'>
                            <a href='<?php echo $food['url'] ?>'>
                                <image class='profile' src='<?php echo $food['image'] ?>'></image>
                                <div class='blockbody'>
                                    <h3><?php echo $food['name'] ?></h3>
                                </div>
                            </a>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
    </div>

</body>