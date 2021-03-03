<head>
    <script src="lib/wwgo.js"></script>
    <link href='style/style.css' rel='stylesheet'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
</head>

<?php
include('lib/wwgo.php');

use wwgo\visual;


?>

<body>
    <?php $build = new visual();
    echo $build->header(); ?>
    <span class='spacer'></span>

    <div class='banner' style="display: table; height: 400px; #position: relative; overflow: hidden;">
        <div style="#position: absolute; #top: 50%;display: table-cell; vertical-align: middle;">
            <div style="#position: relative; #top: -50%">
                <h1>What We Grubbin' On?</h1>
                <span>Find out what you're going to eat tonight. Make a list of interesting foods and have our app randomly suggest one for tonight</span>
            </div>
        </div>
    </div>

    <div class='row'>
        <?php
        ?>
    </div>

</body>

<?php
