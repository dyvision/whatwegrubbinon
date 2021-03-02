<head>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <script src="lib/wwgo.js"></script>
    <link href='style/style.css' rel='stylesheet'>
    <meta name="google-signin-client_id" content="634372968316-6p6nf6j795lbja68pd6q35c74pqjb55s.apps.googleusercontent.com">
    <meta name='viewport' content='width=device-width, initial-scale=1'>
</head>

<?php
$header = "<div id='navbar'><h3 class='navbar-item'>What We Grubbin' On</h3><h3 class='navbar-item'>Food</h3><h3 class='navbar-item'>Profile</h3><div class='g-signin2' data-redirecturi='https://whatwegrubbinon.com/profile.php' data-onsuccess='onSignIn'></div></div>";
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
