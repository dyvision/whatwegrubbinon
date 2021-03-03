<head>
    <script src="lib/wwgo.js"></script>
    <link href='style/style.css' rel='stylesheet'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='shortcut icon' type='image/png' href='style/wwgologo.png'/>
    <title>WWGO</title>
</head>

<?php
include('lib/wwgo.php');

use wwgo\visual;


?>

<body>
    <?php $build = new visual();
    echo $build->header(); ?>

    <div class='banner' style="display: table; #position: relative; overflow: hidden;">
        <div style="#position: absolute; #top: 50%;display: table-cell; vertical-align: middle;">
            <div style="padding:0 3%;#position: relative; #top: -50%">
                <h1 style='font-size:72px'>What We Grubbin' On?</h1>
                <h3 style='font-size:30px'>Find out what you're going to eat tonight.</h3>
                <h3 style='font-size:30px'>Add recipes.</h3>
                <h3 style='font-size:30px'>Get recommendations.</h3>
            </div>
        </div>
    </div>

    <div class='row'>
        <?php
        ?>
    </div>

</body>

<?php
