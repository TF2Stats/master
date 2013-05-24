<?php 
require_once('ajax.php');

$term = '%'.$_REQUEST['term'].'%';

$db->query("SELECT ip, port, name FROM tf2_servers WHERE name LIKE %s OR ip LIKE %s OR tags LIKE %s LIMIT 10",
		array($term,$term,$term));
if(!$db->num_rows())
	exit('["Nothing found.",]');
while($row = $db->fetch_array())
	$ret[] = array('label' => $row['name'], 'value' => sprintf('%s:%s',$row['ip'],$row['port']));
print(json_encode($ret));
?>