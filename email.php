<?php

include('lib/wwgo.php');
header('content-type: application/json');

use wwgo\recommendation;

$times = [];

$outputs = array(
    'night' => 18,
    'lunch' => 12,
    'morning' => 8
);

$settime = gmdate("Y-m-d ").'00:00';

$now = gmdate("Y-m-d H").':00';

$rec = new recommendation('113077615898413620126');
$recs = json_decode($rec->get(), true);
foreach ($recs as $email) {
    
    $now = date('Y-m-d H',(gmmktime())+(60*60*$email['tz'])).':00';
    $schedtime = date('Y-m-d H',strtotime($settime)+(60*60*($outputs[$email['type']]))).':00';

    $script['tid'] = $email['tid'];
    $script['script_run'] = $now;
    $script['scheduled_time'] = $schedtime;
    if ($now == $schedtime) {
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
