<?php

include('lib/wwgo.php');

use wwgo\recommendation;

$outputs = '';

$outputs['night'] = '18';
$outputs['mondays'] = '8';
$outputs['morning'] = '8';
$outputs['month'] = '8';

$now = gmdate("Y-m-d H").':00';

$rec = new recommendation('113077615898413620126');
$recs = json_decode($rec->get(), true);
foreach ($recs as $email) {
    echo $now;
    $hour = ($outputs[$email['type']] + $email['tz']).':00';
    echo gmdate("Y-m-d ").$hour;
    if ($now == $outputs[$email['type']] + $email['tz']) {
        $gen = new recommendation($email['id']);
        $result = json_decode($gen->generate(), true);
        $gen->send($result['rid'], $email['email']);
    }
}
