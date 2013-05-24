<?php 

require_once('cache.php');
cache::inc('tf2_items_schema.php');



class backpack_old
{
	private $id64;
	private $error;
	private $info;
	public $equipped;
	public $fresh = false;
	
	function __construct($player)
	{
		global $db, $settings, $schema;
		$this->equipped = $this->default_loadout();
		$id = $player->id();
		$id64 = $player->id64();

		$backpack_info = $db->query_first ("SELECT time FROM tf2_backpacks WHERE player_id = %s",array($id));
		if(!$backpack_info || $backpack_info['time'] < time () - $settings['cache']['backpack'])
			backpack::refresh($id64, $id);
		
		$db->query("SELECT * FROM tf2_backpack_items WHERE player_id = %s",array($id));
		while($i = $db->fetch_array())
			$items[sprintf("%u",$i['id'])] = $this->get_item($i);

		//var_dump($items);
		foreach($items as $i)
		{
			$item_ids[] = $i['id'];
			//unset($items[$i['id']]['attributes']);
			//$items[$i['id']] = $i;
		}
		
		$in = implode(',',$item_ids);
		
		$db->query("SELECT item_id, defindex, value FROM tf2_backpack_item_attributes WHERE item_id IN ($in)");
		while($row = $db->fetch_array())
			$items[$row['item_id']]['attributes'][] = $row;

		$this->items = $items;
		//echo "<pre>";
		//var_dump($items);
		//echo "</pre>";
	}
	function refresh($id64, $id)
	{
		global $db, $settings;
		if($this->fresh)
			return;
		$this->fresh=true;
		$response = cache::get(sprintf('http://api.steampowered.com/ITFItems_440/GetPlayerItems/v0001/?key=%s&SteamID=%s',$settings['api_key'],$id64));
		$json = json_decode($response, true);
		$items = $json['result']['items']['item'];
		// get a list of old items
		$db->query("SELECT * FROM tf2_backpack_items WHERE player_id = %s",array($id));
		while($row = $db->fetch_array())
			$old_items[$row['id']] = $row;
			
		//var_dump($response);
		echo "<pre>";
		foreach($items as $item)
		{
			var_dump($item);
			
			$item['id'] = sprintf("%u",$item['id']);
			$old = $old_items[$item['id']];

			if(	$old['id'] != $item['id'] ||
				$old['player_id'] != $id ||
				$old['inventory'] != $item['inventory'] ||
				$old['defindex'] != $item['defindex'])
					backpack::insert_item($id,$item);

			unset($old_items[$item['id']]);
		}
		echo "</pre>";
		if($old_items)
			foreach($old_items as $i)
			{
				// prune deleted items
				echo "DELETE ".$i['id'].'<br>';
				$db->query("DELETE FROM tf2_backpack_items WHERE id = %s",array($i['id']));
			}
			
		
		$db->query("INSERT INTO tf2_backpacks(player_id, time) VALUES (%s, %s) ON DUPLICATE KEY UPDATE time=%s",
			array($id, time(), time()));
	}
	function get_item($i)
	{
		global $schema;
		$i['id'] = sprintf("%u",$i['id']);
		$it = $schema['items'][$i['defindex']];
		foreach($i as $key => $var)
			$it[$key] = $var;
		$it['position'] = $i['inventory'] & 0x0000FF;
		$e = get_equipped($i['inventory']);
		$it['equipped'] = $e;
		if($e)
			foreach($e as $c)
			{
				$cn = int_to_class($c);
				$it['equipped_by'][$cn] = true;
				$this->equipped[$cn][$it['item_slot']] = $it;
			}
		return $it;
	}
	
	function insert_item($id, $item)
	{
		//echo "INSERTING ITEM ".$item['id'];
		global $db;
		$db->query("INSERT INTO tf2_backpack_items (player_id, id, defindex, level, inventory, quality) VALUES (%s, %s, %s, %s, %s, %s)
			ON DUPLICATE KEY UPDATE player_id=%s, defindex=%s, level=%s, inventory=%s, quality=%s",
			array($id, $item['id'], $item['defindex'], $item['level'], $item['inventory'], $item['quality'],
					 $id, $item['defindex'], $item['level'], $item['inventory'], $item['quality'] ));
		if($item['attributes']['attribute'])
			foreach($item['attributes']['attribute'] as $attr)
			{
				$db->query("INSERT INTO tf2_backpack_item_attributes (item_id, defindex, value) VALUES (%s, %s, %s)
							ON DUPLICATE KEY UPDATE value=%s",
						array($item['id'],$attr['defindex'],$attr['value'],$attr['value']));
			}
	}
	function update_schema()
	{
		global $db, $settings;
		$response = cache::get(sprintf('http://api.steampowered.com/ITFItems_440/GetSchema/v0001/?key=%s&format=json',$settings['api_key']));
		$json = json_decode($response, true);
		$s = $json['result'];
		$s['items'] = $s['items']['item'];
		foreach($s['items'] as $k => $i)
		{
			$url = $i['image_url'];
			$bits = explode('/',$i['image_url']);
			$im = $bits[count($bits)-1];
			
			$s['items'][$k]['image'] = $im;
			$img = $settings['upload']['folder']['items'].$im;
			if(!file_exists($img))
				file_put_contents($img,file_get_contents($url));
		}
		$php = var_export($s,true);
		cache::write('tf2_items_schema.php',sprintf('<?php global $schema; $schema = %s ?>',$php));
	}
	function default_loadout()
	{
		global $schema;
		$items_game = $schema;
		return array (
			'medic' => array (
				'primary' => $items_game['items'][17],
				'secondary' => $items_game['items'][29],
				'melee' => $items_game['items'][8]
			),
			'scout' => array (
				'primary' => $items_game['items'][13],
				'secondary' => $items_game['items'][23],
				'melee' => $items_game['items'][0]
			),
			'sniper' => array (
				'primary' => $items_game['items'][14],
				'secondary' => $items_game['items'][16],
				'melee' => $items_game['items'][3]
			),
			'soldier' => array (
				'primary' => $items_game['items'][18],
				'secondary' => $items_game['items'][10],
				'melee' => $items_game['items'][6]
			),
			'demoman' => array (
				'primary' => $items_game['items'][20],
				'secondary' => $items_game['items'][19],
				'melee' => $items_game['items'][1]
			),
			'heavy' => array (
				'primary' => $items_game['items'][15],
				'secondary' => $items_game['items'][11],
				'melee' => $items_game['items'][5]
			),
			'pyro' => array (
				'primary' => $items_game['items'][21],
				'secondary' => $items_game['items'][12],
				'melee' => $items_game['items'][2]
			),
			'spy' => array (
				'secondary' => $items_game['items'][24],
				'pda2' => $items_game['items'][30],
				'melee' => $items_game['items'][4],
		
			),
			'engineer' => array (
				'primary' => $items_game['items'][9],
				'secondary' => $items_game['items'][22],
				'melee' => $items_game['items'][7],
		
			)
		);
	}
}

define('NO_ITEM',4599);

define('SLOT_NONE',		0);
define('SLOT_PRIMARY',	1);
define('SLOT_SECONDARY',2);
define('SLOT_MELEE',	3);
define('SLOT_HEAD',		4);
define('SLOT_MISC',		5);
define('SLOT_PDA',		6);
define('SLOT_PDA2',		7);

define('NOT_SPECIAL',		0);
define('SPECIAL_COSMETIC',	1);

global $items_game, $blank_item, $CLASS_SLOTS;

$CLASS_SLOTS = array(
	'medic' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'scout' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'sniper' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'heavy' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'spy' => array('secondary', 'pda2', 'melee', 'head', 'misc'),
	'engineer' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'soldier' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'pyro' => array('primary', 'secondary', 'melee', 'head', 'misc'),
	'demoman' => array('primary', 'secondary', 'melee', 'head', 'misc')
);


// support functions
function int_to_class($int)
{
	switch ($int)
	{
		case '0':
			return 'scout';
		case '1':
			return 'sniper';
		case '2':
			return 'soldier';
		case '3':
			return 'demoman';
		case '4':
			return 'medic';
		case '5':
			return 'heavy';
		case '6':
			return 'pyro'; // IS SPY
		case '7':
			return 'spy';
		case '8':
			return 'engineer';
	}
	return 'none';
}
function class_to_int($int)
{
	switch ($int)
	{
		case 'scout':
			return '0';
		case 'sniper':
			return '1';
		case 'soldier':
			return '2';
		case 'demoman':
			return '3';
		case 'medic':
			return '4';
		case 'heavy':
			return '5';
		case 'pyro':
			return '6'; // IS SPY
		case 'spy':
			return '7'; // IS PYRO!
		case 'engineer':
			return '8';
	}
	return '-1';
}

function slot_to_int($i)
{
	switch($i['item_slot'])
	{
		case 'primary':
			return SLOT_PRIMARY;
		case 'secondary':
			return SLOT_SECONDARY;
		case 'melee':
			return SLOT_MELEE;
		case 'head':
			return SLOT_HEAD;
		case 'misc':
			return SLOT_MISC;
		case 'pda':
			return SLOT_PDA;
		case 'pda2':
			return SLOT_PDA2;
		default:
			return SLOT_NONE;
			
	}
}
function is_equipped($class,$position)
{
	return ($position & 0x80000000) &&
      ($position & (0x00010000 << $class)); 
}
function get_equipped($position)
{
	$ret = array();
	for($x=0;$x<=8;$x++)
		if(is_equipped($x,$position))
			$ret[] = $x;
	return $ret;
}
?>