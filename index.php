<?php
$runtime = microtime(true);

if($_SERVER['HTTP_HOST'] == 'www.tf2stats.net' || $_SERVER['HTTP_HOST'] == 'mk3.tf2stats.net' || $_SERVER['HTTP_HOST'] == 'old.tf2stats.net')
{
	header('Location: http://tf2stats.net'.$_SERVER['REQUEST_URI']);
	exit();
}

/*if(date('m/d/y') == '1/18/12')
{
	header('Location: http://sopablackout.org/learnmore/');
	die();
}*/

if(false && $_SERVER['REMOTE_ADDR'] == '::ffff:75.179.179.209')
{	ini_set('display_errors',1);
	ini_set('error_reporting',E_ALL ^ E_NOTICE);
} else
	ini_set('display_errors',0);

// image check
$b=explode('/',$_REQUEST['page']);
if($b[0] == 'i')
{
	require_once('i.php');
	exit();
}

libxml_use_internal_errors(true);

require_once('includes/common.php');
//echo $_REQUEST['page'];
//if($_REQUEST['page'] != 'dead')
//	header('Location: http://tf2stats.net/dead');
page::load($_REQUEST['page']);
//page::load("dead");

//$page->error("ERROR l4ZY","Programmer is lazy.");

//printf("<!-- %s -->", $user->id());

if( IsUserAdmin() )
	printf( "<pre>%s\nTotal run time: %s</pre>", $CACHE_LOG, number_format( microtime(true) - $runtime, 3 ) );
?>