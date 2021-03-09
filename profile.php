<?php
include('lib/wwgo.php');

use wwgo\auth;
use wwgo\user;
use wwgo\visual;
use wwgo\recipe;

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

$u = $_COOKIE['id'];
$p = $_COOKIE['guid'];
?>

<head>
    <script src="lib/wwgo.js"></script>
    <link href='style/style.css' rel='stylesheet'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='shortcut icon' type='image/png' href='style/wwgologo.png' />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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
                    <image class='profile' src=<?php echo "'" . str_replace('s96','s240',$profile['image']) . "'"; ?>></image>
                    <div class='blockbody'>
                        <h3><?php echo $profile['fullname'] ?></h3>
                        <h3>Get Recommendations</h3>
                        <?php if($_GET['message'] == 1){echo '<h4 style="color:lightblue">Schedule Set</h4>';} ?>
                        <form action='https://<?php echo $u ?>:<?php echo $p ?>@whatwegrubbinon.com/api/recommendation' method='POST'>
                            <?php $build->timezone(); ?>
                            <select name='type'>
                                <option value='morning'>Every Breakfast</option>
                                <option value='lunch'>Every Lunch</option>
                                <option value='night'>Every Dinner</option>
                            </select>
                            <button >Update Frequency</button>
                        </form>
                        <form action="https://<?php echo $u ?>:<?php echo $p ?>@whatwegrubbinon.com/api/recipe" method='POST'>
                            <input required type='text' name='name' placeholder='Recipe Name'>
                            <!--<input required type='text' name='image' placeholder='Recipe Image'>-->
                            <input required type='text' name='url' placeholder='Recipe URL'>
                            <button class='recipe-button'>Add A Recipe</button></br>
                        </form>
                        
                        <span style='color:grey'>API Key: <?php echo $profile['id'] ?></span></br>
                        <span style='color:grey'>API Secret: <?php echo $profile['guid'] ?></span>

                    </div>
                </div>
                <div id='list' class='bigblock'>
                    <h1 style='margin: 1%;font-size: 60;color: white;text-shadow: 0px 4px 6px #00000091;'>Recipes</h1>
                    <?php
                    $recipe = new recipe($_COOKIE['id']);
                    $recipes = json_decode($recipe->get(), true);
                    foreach ($recipes as $food) { 
                        if(strlen($food['name']) > 34){
                            $foodname = substr($food['name'],0,35).'...';
                        } else {
                            $foodname = $food['name'];
                        }
                        ?>
                        <div if="<?php echo $food['rid']; ?>" class='innerblock'>
                            <a href='<?php echo $food['url'] ?>'>
                                <image class='profile' src='<?php echo $food['image'] ?>'></image></a>
                                <div  class='blockbody'>
                                    <h3><?php echo $foodname ?></h3>
                                    <button type='button' class='recipe-button' <?php echo $disabled; ?> onclick="delete_recipe(<?php echo '\''.$food['rid'].'\',\''.$_COOKIE['id'].'\',\''.$_COOKIE['guid'].'\''; ?>);">Remove Recipe</button>
                                
                                </div>
                            
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
    </div>

</body>