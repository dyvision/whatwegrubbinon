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
<h3 class='navbar-item'><a href='profile.php'>Login with Google</a></h3>
</div>";
}
?>

<body>
    <?php echo $header; ?>
    <span class='spacer'></span>

    <center class='banner'>
        <div class='banner-title'>
            <h1>What We Grubbin' On?</h1>
            <span>Find out what you're going to eat tonight. Make a list of interesting foods and have our app randomly suggest one for tonight</span>
        </div>
    </center>
    <div class='row'>
        <?php
        ?>
    </div>

</body>

<?php
