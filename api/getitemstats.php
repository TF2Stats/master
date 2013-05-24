<?php
ini_set('display_errors',1);

require_once('api.php');
$json_file = 'item_stats.json';
$json = cache::read($json_file);
echo $json;


?>