<?php

include('lib/wwgo.php');

use wwgo\recommendation;

$outputs = '';

$outputs['night'] = '18:00';
$outputs['mondays'] = '8:00';
$outputs['morning'] = '8:00';
$outputs['month'] = '8:00';

$now = gmdate("Y-m-d H").':00';

$rec = new recommendation('113077615898413620126');
$recs = json_decode($rec->get(), true);
foreach ($recs as $email) {
    echo $now;
    echo gmdate("Y-m-d ").$outputs[$email['type']] + $email['tz'];
    if ($now == $outputs[$email['type']] + $email['tz']) {
        $gen = new recommendation($email['id']);
        $result = json_decode($gen->generate(), true);
        $gen->send($result['rid'], $email['email']);
    }
}
