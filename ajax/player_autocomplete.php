<?php 
require_once('ajax.php');

$term = '%'.$_REQUEST['term'].'%';

$db->query("SELECT name, id64, id FROM tf2_players WHERE name LIKE %s OR id64 LIKE %s OR custom_url LIKE %s LIMIT 10",
		array($term,$term, $term));
while($row = $db->fetch_array())
	$ret[] = array('label' => $row['name'], 'value' => $row['id64'],'id' => $row['id']);
print(json_encode($ret));
?>