<?php 
// Cloudflare fix.
if ($_SERVER["HTTP_CF_CONNECTING_IP"]) { $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"]; }

// Required classes
require_once('settings.php');
require_once('functions.php');
require_once('libs/dwoo/dwooAutoload.php'); 
require_once('classes/db.php');
require_once('classes/page.php');
require_once('classes/session.php');
require_once('classes/view.php');
require_once('classes/player.php');

define('k_EResultOK', 1);

// Global
global $db, $dwoo, $page, $SITE, $user, $session;

// Instantiate 
$db = new db($settings['db']['url'], $settings['db']['user'], $settings['db']['pass'], $settings['db']['db']);
$db->query("SET NAMES 'utf8'");
$dwoo = new Dwoo(); 
$session = new session();

if($session->valid())
{
	$user = new player($session->id64());
}

// Track refs
if($_SERVER['HTTP_REFERER'] && stripos($_SERVER['HTTP_REFERER'],'http://mk3.tf2stats.net') === false && stripos($_SERVER['HTTP_REFERER'],'http://tf2stats.net') === false && stripos($_SERVER['HTTP_REFERER'],'http://www.tf2stats.net') === false)
{
	$db->query("INSERT INTO tf2stats_ref (source, dest, count) VALUES (%s, %s, 1) ON DUPLICATE KEY UPDATE count = count + 1",
		array($_SERVER['HTTP_REFERER'],$_SERVER['REQUEST_URI']));
}

function IsUserAdmin()
{
	global $user;
	return isset($user) && ( $user->id() == 1 || $user->id() == 3672 );
}

?>