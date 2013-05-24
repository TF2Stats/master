<?php 
require_once('ajax.php');

$term = '%'.$_REQUEST['term'].'%';

$db->query("SELECT name FROM tf2_maps WHERE name LIKE %s ORDER BY score DESC LIMIT 10",
		array($term));
if(!$db->num_rows())
	exit('["Nothing found.",]');
while($row = $db->fetch_array())
	$ret[] = $row['name'];
print(json_encode($ret));
?>