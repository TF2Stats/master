<?php 

/**
 * jquery style extend
 *
 * @return array $extended
 **/
function extend() {
	$args = func_get_args();
	$extended = array();
	if(is_array($args) && count($args)) {
		foreach($args as $array) {
			if(is_array($array)) {
				$extended = array_merge($extended, $array);
			}
		}
	}
	return $extended;
}
/**
 * 
 * int conversion which strips all non-int values
 * 
 * @param string $string
 * @param boolean $concat
 * @return int result
 */
function str2int($string, $concat = true) {
    $length = strlen($string);   
    for ($i = 0, $int = '', $concat_flag = true; $i < $length; $i++) {
        if (is_numeric($string[$i]) && $concat_flag) {
            $int .= $string[$i];
        } elseif(!$concat && $concat_flag && strlen($int) > 0) {
            $concat_flag = false;
        }       
    }
   
    return (int) $int;
}
function is_id64($id)
{
	if(!ctype_digit($id) || $id < 76561197960265728)
	{
		return false;
	}
	return true;
}
function get_id64($player_id)
{
	global $db, $settings;
	if(!is_id64($player_id))
		$url = sprintf("http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=%s&vanityurl=%s", $settings['api_key'] ,$player_id);
	else
		return $player_id;
		
	// Try the easy way
	$i = $db->query_first("SELECT id64 FROM tf2_players WHERE custom_url = %s",array($player_id));
	if($i['id64'])
		return $i['id64'];	

	require_once('classes/cache.php');
	$json_string = cache::get($url);
	try {
		$rgResult = json_decode($json_string, true);
		$rgResponse = $rgResult['response'];
	} catch(Exception $e) {
		// Bad XML data. Purge and throw an error
		cache::purge($url);
		$bad_xml = true;
		return false;
		//echo "BAD XML!!!: ".$url; 
	}
	if( $rgResponse['success'] == k_EResultOK )
	{
		$id64 = $rgResponse['steamid'];
		// Update this in the db
		$db->query("UPDATE tf2_players SET custom_url=%s WHERE id64=%s",array($player_id,$id64));
	}
	
	return $id64;
}
function soft_nl2br($txt)
{
	$txt = str_replace('\n','<br>',$txt);
	return nl2br($txt);
}
?>