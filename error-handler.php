<?php 
$bits = explode('/',$_SERVER['REQUEST_URI']);

// Missing image! Hooray!
if($bits[2] == 'images')
{
	$dir = $bits[3];
	$query = $bits[5];
	$file = $bits[6];
	$ib = explode('.',$file);
	if(count($ib) == 3)
	{
		$paintcolor = $ib[1];
		$im = array($ib[0],$ib[2]);
		$requested_image = implode('.',$im);
	} else
		$requested_image = $file;
	if(!$bits[6])
		die('Error: 404');
}
include('i.php');
?>