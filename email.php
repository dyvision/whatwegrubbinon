<?php

include('lib/wwgo.php');

use wwgo\recommendation;

$times = [];

$outputs = '';

$outputs['night'] = '18';
$outputs['mondays'] = '8';
$outputs['morning'] = '8';
$outputs['month'] = '8';

$now = gmdate("Y-m-d H").':00';

$rec = new recommendation('113077615898413620126');
$recs = json_decode($rec->get(), true);
foreach ($recs as $email) {
    $script['script_run'] = $now;
    $hour = ($outputs[$email['type']] + $email['tz']).':00';
    $script['scheduled_time'] = gmdate("Y-m-d ").$hour;
    if ($now == $outputs[$email['type']] + $email['tz']) {
        $gen = new recommendation($email['id']);
        $result = json_decode($gen->generate(), true);
        $gen->send($result['rid'], $email['email']);
        $script['executed'] = 'yes';
    }else{
        $script['executed'] = 'no';
    }
    array_push($times,$script);

}
print_r(json_encode($times));
