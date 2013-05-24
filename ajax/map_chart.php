<?php 
return;
require_once('ajax.php');

require_once('classes/map.php');

$map = new map($_REQUEST['map']);

$history = $map->get_history();
$r=0;
foreach($history as $h)
{
	$d = getdate($h['time']);
	$d['row'] = $r++;
	$d['players'] = $h['players'];
	$d['slots'] = $h['slots'];
	$d['servers'] = $h['servers'];
	$rows[] = sprintf("[new Date(%s,%s,%s,%s,%s),%s,%s,%s]",$d['year'],$d['mon']-1,$d['mday'],$d['hours'],$d['minutes'],$h['players'],$h['servers'],$h['slots']);//,$h['slots']);
}


		
printf('[%s]',implode(',',$rows));
?>