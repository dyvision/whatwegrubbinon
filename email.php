<?php

include('lib/wwgo.php');

use wwgo\recommendation;

$outputs = '';

$outputs['night'] = '18:00';
$outputs['all'] = ['8:00','12:00','18:00'];
$outputs['mondays'] = '12:00';
$outputs['morning'] = '8:00';
$outputs['month'] = '8:00';


$rec = new recommendation('113077615898413620126');
$recs = json_decode($rec->get(),true);
foreach($recs as $email){
    $gen = new recommendation($email['id']);
    $result = json_decode($gen->generate(),true);
    $gen->send($result['rid'],$email['email']);
}
