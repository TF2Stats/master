<?php 
require_once('cache.php');
cache::inc('tf2_items_schema.php');
cache::inc('tf2_language_english.php');

// SCHEMA FIX! HACK HACK HACKKKK
global $schema, $CRATE_CONTENTS;
$schema['items'][124]['item_slot'] = '';
$hides = array(25, 28, 195, 196, 142, 144);

foreach($hides as $h)
	$schema['attributes'][$h]['hidden'] = 1;

// END HACKY CODE

// Supply crates! (TODO: move this somewhere else plz)
define('CRATE_SPECIAL_ITEM',8000);

$CRATE_CONTENTS = array (
	1 => array (173, 142, 128, 130, 247, 248, 5020, 5039, 5040, CRATE_SPECIAL_ITEM),
	2 => array (228, 220, 224, 255, 246, 250, 241, 5031, 5037, CRATE_SPECIAL_ITEM),
	3 => array (232, 163, 153, 150, 249, 251, 5044, 5040, CRATE_SPECIAL_ITEM),
	4 => array (226, 154, 225, 174, 185, 253, 241, 5039, CRATE_SPECIAL_ITEM),
	5 => array (5020, 214, 133, 155, 181, 178, 177, 5030, CRATE_SPECIAL_ITEM),
	6 => array (329, 317, 331, 326, 327, 340, 319, 341, 339, 322, 342, 330, 315, 321, 323, 316, 337, 338, 314, 324, 313, CRATE_SPECIAL_ITEM), // Festive crate!
	7 => array (131, 127, 232, 47, 252, 216, 219, 5044, 5046, CRATE_SPECIAL_ITEM),
	8 => array (45, 46, 56, 53, 291, 290, 223, 241, 5042, CRATE_SPECIAL_ITEM),
	9 => array (5051, 5052, 5053, 5054, 5055, 5056, 227, CRATE_SPECIAL_ITEM)
);
	
// END supply crates

class backpack
{
	private $id64;
	private $error;
	private $info;
	public $equipped;
	public $items;
	public $fresh = false;
	
	function __construct( $player, $multi=false )
	{
		global $settings;

		if(!$player)
			return;

		$this->player = $player;
		$id64 = $player->id64();
		$key = 'BACKPACK_'.$id64;


		$cache = Cache::Memcached()->get( $key );
		if( $cache === false )
		{
			cache::log( sprintf('Memcached MISS for %s (result: %s)', $key, Cache::Memcached()->getResultCode() ) );

			$url = sprintf('http://api.steampowered.com/IEconItems_440/GetPlayerItems/v0001/?key=%s&SteamID=%s',$settings['api_key'],$id64);
			if( $multi )
			{
				cache::register_multi_url( $url, array( $this, 'initialize' ) );
			} else {
				$response = cache::get( $url );

				if(!$response)
				{
					$this->error = true;
					$this->message = "Steam API request failed! Steam community may be down. Please try again at a later time.";
				}

				$this->initialize($response);
			}
		} else {
			cache::log( sprintf('Memcached HIT for %s', $key ) );
			$this->initialize();
		}

	}

	function initialize( $json_file = false )
	{
		global $db, $settings, $schema;

		$player = $this->player;

		$this->equipped = $this->default_loadout();
		$id = $player->id();
		$id64 = $player->id64();
		$key = 'BACKPACK_'.$id64;

		if( !$json_file )
		{
			global $_CACHE_ITEMS, $_CACHE_EQUIPPED;
			$cache = Cache::Memcached()->get( $key );
			$this->items = $cache['items'];
			$this->equipped = $cache['equipped'];
			//var_dump($_CACHE_EQUIPPED);
			return;
		}
		$this->equipped = backpack::default_loadout();



		$json = json_decode($json_file, true);
		/*?><pre><?php
		var_dump($json);
		?></pre><?php*/
		if(!$json)
		{
			// Try forcing UTF8
			$response = utf8_encode($json_file);
			$json = json_decode($response, true);
		}
		$json_items = $json['result']['items'];
		if(!$json_items)
		{
			$this->error = true;
			$this->message = "Backpack is empty.";
		} else {

			foreach($json_items as $i)
				$items[] = $this->get_item($i);
		}

		// create cache file
		$cache = array(
			'items' => $items,
			'equipped' => $this->equipped
		);

		$this->items = $items;
		//$this->equipped = $equipped;

		cache::Memcached()->set( $key, $cache );
		//cache::clean('backpack');
	}

	function get_item($i)
	{
		global $schema;
		$i['id'] = sprintf("%u",$i['id']);
		$it = $schema['items'][$i['defindex']];
		if(is_array($it['attributes']))
			foreach($it['attributes'] as $a)
				$indexed[$a['name']] = $a;
		
		$it['attributes'] = $indexed;
		if($i['attributes'])
		{
			foreach($i['attributes'] as $a)
			{
				// Remove existing attrs with this index
				$v = $schema['attributes'][$a['defindex']];
				$v['value'] = $a['value'];
				$v['float_value'] = $a['float_value'];
				$it['attributes'][$v['name']] = $v;

			}	
		}
		foreach($i as $key => $var)
			if($key != 'attributes')
				$it[$key] = $var;
		$it['position'] = $i['inventory'] & 0x0000FFFF;
		//$e = get_equipped($i['inventory']);
		//$e = equipped_convert($i['equipped']);
		
		$it['equipped'] = $i['equipped'];
		// language keys
		/*$it['type'] = get_lang_key('ItemTypeDesc',array(
			1 => ($it['level'] > 0) ? $it['level'] : 1,
			2 => get_lang_key($it['item_type_name'])
			));*/
		
		$it['type'] = sprintf("Level %s %s",(isset($it['level'])) ? $it['level'] : 1, get_lang_key($it['item_type_name']));
		$it['name'] = htmlspecialchars(get_item_name($it));
		$it['name_url'] = rawurlencode(preg_replace("/[^a-zA-Z0-9]/","-",$it['name']));
		$it['desc'] = $it['item_description'];
		if($i['custom_desc'])
			$it['desc'] = htmlspecialchars($i['custom_desc']);
		
		//$it['attributes'] = $it['attributes']['attribute'];
		$dd=0;
		if($it['attributes'])
			foreach($it['attributes'] as $a)
			{
				$a_class = get_attribute($a['name']);
				
				if($a['class'] == 'set_item_tint_rgb')
				{
					$it['color'] =  attribute_value($a);
					//echo $a['value']."<br>";
				}
				if($a['name'] == 'set supply crate series')
				{
					$series = attribute_value($a);
					$it['crate_series'] = $series;
					$it['tooltip_tail'] .= get_crate_tooltip($series);
					//echo $it['tooltip_tail'];
				}
				if($a['name'] == 'kill eater')
				{
					$it['kill_eater_kills'] = attribute_value($a);
					$it['kill_eater_rank'] = get_kill_eater_rank($a);
				}
				
				
				$value = $a['value'];
				//echo $a_class['description_string'];
				
				//$description = sprintf($format_string,$value);
				if($a['hidden'] == "1" || $a_class['hidden'] == "1")
					continue;
				$desc = get_attribute_text($value, $a_class, $a['float_value']); 
				$it['attrs'][] = $desc;
				
				// Colors use format imagename.color.png
				//var_dump($a);
				//die();
				
			}
			
		
		$bits = explode('/',$it['image_url']);
		$it['image'] = $bits[count($bits)-1];
		
		$it['tooltip'] = backpack::generate_tooltip($it);
		
		if($it['color'] && $it['image_inventory'] == 'backpack/player/items/crafting/paintcan')
			$it['image'] = str_replace('.png','.'.$it['color'].'.png',$it['image']);
		
		if($it['equipped'])
			foreach($it['equipped'] as $e)
			{
				$cn = int_to_class($e['class']);
				$it['equipped_by'][$cn] = true;
				$this->equipped[$cn][int_to_slot($e['slot'])] = $it;
			}
		
		return $it;
	}
	
	public static function generate_tooltip( $i )
	{
		// TODO: custom_name, custom_desc, contained_item
		
		$tooltip = '<h1 class="' . int_to_quality( $i[ 'quality' ] ) . '">' . $i[ 'name' ] . '</h1>'
				. '<span class="block note">' . $i[ 'type' ] . '</span>';
		
		if( isset( $i[ 'kill_eater_rank' ] ) )
		{
			$tooltip .= '<span class="block">' . $i[ 'kill_eater_rank' ] . ' (' . $i[ 'kill_eater_kills' ] . ' kills)</span>';
		}
		
		if( isset( $i[ 'origin' ] ) )
		{
			$tooltip .= '<span class="block">Origin: ' . int_to_origin( $i[ 'origin' ] ) . '</span>';
		}
		
		if( isset( $i[ 'attrs' ] ) )
		{
			foreach( $i[ 'attrs' ] as $a )
			{
				$tooltip .= '<span class="block ' . $a[ 'type' ] . '">' . $a[ 'desc' ] . '</span>';
			}
		}
		
		if( !empty( $i[ 'desc' ] ) )
		{
			$tooltip .= '<br>' . $i[ 'desc' ];
		}
		
		if(isset($i['flag_cannot_trade']))
			$tooltip .= '<span class="block note">Not Tradable</span>';
		
		if(isset($i['flag_cannot_craft']))
			$tooltip .= '<span class="block note">Not Craftable</span>';
		
		if( !empty( $i['tooltip_tail' ] ) )
		{
			$tooltip .= '<br>' . $i['tooltip_tail' ];
		}
		
		return $tooltip;
	}
	
	/*public static function generate_tooltip($i)
	{
		$tooltip = sprintf('
			<h1 class="%s">%s</h1>
			<span class="block note">%s</span>
			%s',
			int_to_quality($i['quality']),
			$i['name'],
			$i['type'],
			$i['desc']
		);
		
		$tail = '';
		
		if(isset($i['kill_eater_rank']))
			$tail .= sprintf('<span class="block">%s (%s kills)</span>',$i['kill_eater_rank'],$i['kill_eater_kills']);	
		
		//if(isset($i['flag_achievement_granted']))
		//	$tail .= '<span class="block note">Achievement item</span>';
		//if(isset($i['flag_purchased']))
		//	$tail .= '<span class="block note">Store item: Cannot craft, cannot trade</span>';
		//if(isset($i['flag_promotion']))
		//	$tail .= '<span class="block note">Store promotion: Cannot trade, Cannot craft</span>';
		
		if(isset($i['flag_cannot_trade']) || isset($i['flag_cannot_craft']))
		{
			$tail .= '<br>';
			
			if(isset($i['flag_cannot_trade']))
				$tail .= '<span class="block note">Cannot trade</span>';
			if(isset($i['flag_cannot_craft']))
				$tail .= '<span class="block note">Cannot craft</span>';
		}
		
		if($i['attrs'])
			foreach($i['attrs'] as $a)
			{
				$tooltip .= sprintf('
					<span class="block %s">%s</span>
				',
				$a['type'],
				$a['desc']);
			}
		$tooltip .= $tail . $i['tooltip_tail'];
		return $tooltip;
	}*/
	
	function update_schema()
	{
		global $db, $settings;
		$response = cache::get(sprintf('http://api.steampowered.com/IEconItems_440/GetSchema/v0001/?key=%s&format=json&language=en',$settings['api_key']),60);
		$json = json_decode($response, true);
		$s = $json['result'];
		//print $response;
		$s['items'] = $s['items'];
		$s['attributes'] = $s['attributes'];
		if(!isset($s['items']))
			die("FAILED" . PHP_EOL . $response);
		
		foreach($s['items'] as $k => $i)
		{
			//$bits = explode('/',$i['image_url']);
			//$im = $bits[count($bits)-1];
			
			$s['items'][$k]['quality'] = $i['item_quality'];
			//$s['items'][$k]['image'] = basename($i['image_inventory']).'.png';
			//$s['items'][$k]['image_url'] = sprintf('http://media.steampowered.com/apps/440/icons/%s',$s['items'][$k]['image']);
			//$img = $settings['upload']['folder']['items'].basename($i['image_inventory']).'.png';
			
			if( $i[ 'image_inventory' ] == 'backpack/player/items/crafting/paintcan' )
			{
				$s['items'][$k]['image'] = 'paintcan.png';
				$s['items'][$k]['image_url'] = 'http://media.steampowered.com/apps/440/icons/paintcan.png';
				continue;
			}
			
			$url = $i['image_url'];
			$name = basename($url);
			
			if( !strlen( $url ) )
			{
				cache::log(sprintf("<i>(ID: %d)</i> <b>%s</b> — no image!", $i[ 'defindex' ], substr( $i[ 'item_name' ], 0, 60 )));
				
				$s['items'][$k]['image_url'] = 'http://tf2stats.net/images/unknown.png';
				$s['items'][$k]['image'] = 'unknown.png';
				
				continue;
			}
			
			$s['items'][$k]['image'] = $name;
			
			$img = $settings['upload']['folder']['items'] . $name;
			
			if(!file_exists($img))
			{
				//printf("<br><b>%s</b> <i>(ID: %d)</i> - Fetching image at %s", $i[ 'item_name' ], $i[ 'defindex' ], $url);
				cache::log(sprintf("<i>(ID: %d)</i> <b>%s</b> — fetching image at \"%s\"", $i[ 'defindex' ], $i[ 'item_name' ], $url));
				
				$idata = file_get_contents($url);
				
				if(!$idata)
				{
					cache::log(sprintf("<b>File not found.</b> (<a href=\"%s\">check</a>)\n", $url));
					//printf('- <b>File not found on server.</b> (<a href="%s">check</a>)', $url);
				}
				else
				{
					file_put_contents($img,$idata);
				}
			}
			
			if(filesize($img) == 0)
			{
				cache::log("<b>" . $img . ".</b> failed\n");
				@unlink($img);
			}
			
			if(!file_exists($img) || filesize($img) == 0)
			{
				$s['items'][$k]['image_url'] = 'http://tf2stats.net/images/unknown.png';
				$s['items'][$k]['image'] = 'unknown.png';
			}
		}
		
		foreach($s['attribute_controlled_attached_particles'] as $i)
		{
			if( !file_exists( $settings['upload']['folder']['effects'] . $i[ 'id' ] . '.png' ) )
			{
				cache::log("<b>" . $i[ 'name' ] . "</b> - particle image doesn't exist! (ID: " . $i[ 'id' ] . ")");
			}
		}
		
		foreach($s['items'] as $i)
		{
			$o['items'][$i['defindex']] = $i;
		}
		
		foreach($s['attributes'] as $a)
		{
			$o['attributes'][$a['defindex']] = $a;
		}
		
		$o['qualities'] = $s['qualities'];
		$o['origins'] = $s['originNames'];
		$o['particles'] = $s['attribute_controlled_attached_particles'];
		$o['kill_eater_ranks'] = $s['kill_eater_ranks'];
		$php = var_export($o,true);
		cache::writeFile('tf2_items_schema.php',sprintf('<?php global $schema; $schema = %s ?>',$php));
		
		backpack::update_asset_info();
		backpack::update_valve_employees();
	}
	public static function update_asset_info()
	{
		global $db, $settings;
		$response = cache::get(sprintf('http://api.steampowered.com/ISteamEconomy/GetAssetPrices/v0001/?appid=440&key=%s&format=json&language=en',$settings['api_key']),1);
		$json = json_decode($response, true);
		$s = $json['result']['assets'];
		//var_dump($response);
		$out = array();
		$class_ids = array();
		
		foreach($s as $asset)
		{
			$a = $asset;
			foreach($asset['class'] as $c)
				$a[$c['name']] = $c['value'];
			unset($a['class']);
			$out[$a['def_index']] = $a;
			$class_ids[] = $a['classid'];
		}
		
		$class_id_count = count($class_ids);
		$class_id_arg = '';

		for($x=0;$x<$class_id_count;$x++)
		{
			$class_id_arg .= sprintf('&classid%s=%s',$x,$class_ids[$x]);
		}
		
		$response = cache::get(sprintf('http://api.steampowered.com/ISteamEconomy/GetAssetClassInfo/v0001/?appid=440&key=%s&class_count=%s&format=json'.$class_id_arg,$settings['api_key'],$class_id_count),1);
		$json = json_decode($response, true);
		$s = $json['result'];
		
		foreach($out as $key => $item)
			$out[$key] = array_merge($item,$s[$item['classid']]);
		//var_dump($s);
		$php = var_export($out,true);
		cache::writeFile('tf2_item_info.php',sprintf('<?php global $asset_info; $asset_info = %s ?>',$php));
		
	}
	
	public static function update_valve_employees()
	{
		try
		{
			$Data = SimpleXML_Load_File( "http://steamcommunity.com/groups/valve/memberslistxml/", 'SimpleXMLElement', LIBXML_NOCDATA );
			
			$Wat = (Array)$Data->members;
			$Wat = $Wat[ 'steamID64' ];
			
			$php = var_export($Wat,true);
			
			cache::writeFile('valve_employees.php',sprintf('<?php global $VALVE_EMPLOYEES; $VALVE_EMPLOYEES = %s ?>',$php));
		}
		catch( Exception $e )
		{
			//
		}
	}
	
	public function default_loadout()
	{
		global $schema;
		$items_game = $schema;
		return array (
			'medic' => array (
				'primary' => $this->get_item($items_game['items'][17]),
				'secondary' => $this->get_item($items_game['items'][29]),
				'melee' => $this->get_item($items_game['items'][8])
			),
			'scout' => array (
				'primary' => $this->get_item($items_game['items'][13]),
				'secondary' => $this->get_item($items_game['items'][23]),
				'melee' => $this->get_item($items_game['items'][0])
			),
			'sniper' => array (
				'primary' => $this->get_item($items_game['items'][14]),
				'secondary' => $this->get_item($items_game['items'][16]),
				'melee' => $this->get_item($items_game['items'][3])
			),
			'soldier' => array (
				'primary' => $this->get_item($items_game['items'][18]),
				'secondary' => $this->get_item($items_game['items'][10]),
				'melee' => $this->get_item($items_game['items'][6])
			),
			'demoman' => array (
				'primary' => $this->get_item($items_game['items'][19]),
				'secondary' => $this->get_item($items_game['items'][20]),
				'melee' => $this->get_item($items_game['items'][1])
			),
			'heavy' => array (
				'primary' => $this->get_item($items_game['items'][15]),
				'secondary' => $this->get_item($items_game['items'][11]),
				'melee' => $this->get_item($items_game['items'][5])
			),
			'pyro' => array (
				'primary' => $this->get_item($items_game['items'][21]),
				'secondary' => $this->get_item($items_game['items'][12]),
				'melee' => $this->get_item($items_game['items'][2])
			),
			'spy' => array (
				'secondary' => $this->get_item($items_game['items'][24]),
				'pda2' => $this->get_item($items_game['items'][30]),
				'melee' => $this->get_item($items_game['items'][4]),
		
			),
			'engineer' => array (
				'primary' => $this->get_item($items_game['items'][9]),
				'secondary' => $this->get_item($items_game['items'][22]),
				'melee' => $this->get_item($items_game['items'][7]),
		
			)
		);
	}
}

define('NO_ITEM',4599);

define('SLOT_NONE',		-1);
define('SLOT_PRIMARY',	 0);
define('SLOT_SECONDARY', 1);
define('SLOT_MELEE',	 2);
define('SLOT_HEAD',		 7);
define('SLOT_MISC',		 8);
define('SLOT_MISC2',	 10);
define('SLOT_PDA',		 11);
define('SLOT_PDA2',		 6);
define('SLOT_ACTION',	 9);
define('SLOT_PREVIOUS',	 65535);

define('NOT_SPECIAL',		0);
define('SPECIAL_COSMETIC',	1);

global $items_game, $blank_item, $CLASS_SLOTS;

$CLASS_SLOTS = array(
	'medic' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'scout' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'sniper' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'heavy' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'spy' => array('secondary', 'pda2', 'melee', 'head', 'misc', 'misc2', 'action'),
	'engineer' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'soldier' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'pyro' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action'),
	'demoman' => array('primary', 'secondary', 'melee', 'head', 'misc', 'misc2', 'action')
);


// support functions
function int_to_class($int)
{
	switch ($int)
	{
		case '0':
		case '1':
			return 'scout';
		case '2':
			return 'sniper';
		case '3':
			return 'soldier';
		case '4':
			return 'demoman';
		case '5':
			return 'medic';
		case '6':
			return 'heavy';
		case '7':
			return 'pyro'; // IS SPY
		case '8':
			return 'spy';
		case '9':
			return 'engineer';
	}
	return 'none';
}
function class_to_int($c)
{
	switch (strtolower($c))
	{
		case 'scout':
			return '1';
		case 'sniper':
			return '2';
		case 'soldier':
			return '3';
		case 'demoman':
			return '4';
		case 'medic':
			return '5';
		case 'heavy':
			return '6';
		case 'pyro':
			return '7'; // IS SPY
		case 'spy':
			return '8'; // IS PYRO!
		case 'engineer':
			return '9';
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
		case 'action':
			return SLOT_ACTION;
		default:
			return SLOT_NONE;
			
	}
}
function int_to_slot($i)
{
	switch($i)
	{
		
		case SLOT_PRIMARY:
			return 'primary';
		case SLOT_SECONDARY:
			return 'secondary';
		case SLOT_MELEE:
			return 'melee';
		case SLOT_HEAD:
			return 'head';
		case SLOT_MISC:
			return 'misc';
		case SLOT_MISC2:
			return 'misc2';
		case SLOT_ACTION:
			return 'action';
		case SLOT_PREVIOUS:
			return 'previous';
		case SLOT_PDA2:
			return 'pda2';
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

function get_lang_key($key, $args = false, $convert_cr=true)
{
	
	global $lang;
	if(strpos($key,'_') !== false)
	{
		$key = str_replace('#','',$key);
		$l = $lang['Tokens'][$key];
		//echo $key." -&gt; ".$l;
	}
	else
		$l = $key;
	if(!$l)
		return '';
	if($args)
		foreach($args as $n => $v)
			$l = str_replace('%s'.$n,$v,$l);
	if($convert_cr)
		return str_replace('\\n','<br>',$l);
	return $l;
}
function get_clang_key($set, $key, $args=false)
{
	global $lang;
	$l = $lang[$set][$key];
	if(!$l)
		return $key;
	if($args)
		foreach($args as $n => $v)
			$l = str_replace('%s'.$n,$v,$l);
	return $l;
}
function clk($set, $key, $args=false)
{
	return get_clang_key($set, $key, $args);
}
function html_to_paint_name($int)
{
	
}
function get_item_name($i)
{
	global $schema;
	
	if(is_int($i))
		$i = $schema['items'][$i];
	
	if($i['custom_name'])
		return sprintf('"%s"',$i['custom_name']);
	
	$prefix='';
	// Flaregun hack.
	//$key = str_replace('Flaregun','FlareGun',$i['item_name']);

	$prefix = quality_to_label(int_to_quality($i['quality']), false);

	//$q = int_to_quality($i['quality']);
	//if(!in_array($i['quality'], array(0,1,6)))
	//	$prefix = $schema['qualityNames'][$q];
	//var_dump($schema['qualityNames']);
	//die();
	if($prefix)
		return str_replace($prefix.' The', $prefix, $prefix.' '.get_lang_key($i['item_name']));
	return get_lang_key($i['item_name']);
}
function int_to_quality($int)
{
	global $schema;
	foreach($schema['qualities'] as $key => $val)
	{
		if($val == $int)
			return strtolower($key);
	}	
}
function quality_to_label($q, $all=true)
{
	switch($q)
	{
		case 'community': $prefix = 'Community'; break;
		case 'developer': $prefix = 'Valve'; break;
		case 'selfmade': $prefix = 'Self-Made'; break;
		case 'vintage': $prefix = 'Vintage'; break;
		case 'rarity4': $prefix = 'Unusual'; break;
		case 'rarity1':  $prefix = 'Genuine'; break;
		case 'strange':  $prefix = 'Strange'; break;
		case 'haunted':  $prefix = 'Haunted'; break;
		case 'completed':  $prefix = 'Completed'; break;
		case 'unique': if($all) $prefix = 'Unique'; break;
	}
	return $prefix;
}
function quality_to_color($q)
{
	switch($q)
	{
		case 'unique': return '#FFD700';
		case 'community': return '#70B04A';
		case 'developer': return '#A50F79';
		case 'selfmade': return '#70B04A';
		case 'vintage': return '#476291';
		case 'rarity4': return '#8650AC';
		case 'rarity1': return '#4D7455';
		case 'strange': return '#CF6A32';
		case 'completed': return '#8650AC';
		case 'haunted': return '#38F3AB'; // Old: #8650AC
	}
	return '#B2B2B2';
}
function attribute_value($a)
{
	if($a['stored_as_integer'] || $a['float_value'] == null)
		return $a['value'];
	return $a['float_value'];
}

function get_kill_eater_rank($a)
{
	global $schema;
	$val = attribute_value($a);
	krsort($schema['kill_eater_ranks']);
	foreach($schema['kill_eater_ranks'] as $r)
		if($r['required_score'] < $val)
			return $r['name'];
}

function get_attribute_text($val, $a, $fval=0)
{
	global $schema;
	if($val > 1000000000.0 && $a['description_format'] != 'value_is_date') // TODO: Fix this hax. plz.
		$val = $fval;
		
	switch($a['description_format'])
	{
		case 'value_is_additive_percentage':
			$val*=100;
		break;
		case 'value_is_percentage':
			$val*=100;
			$val-=100;
		break;
		case 'value_is_inverted_percentage':
			$val *=100;
			$val = 100-$val;
		break;
		case 'value_is_date':
			if($val > 0)
				$val = date('M j, Y (G:i:s \G\M\T)',$val);
			else
				return '';
		break;
		case 'value_is_particle_index':
			$particle = $schema['particles'][$val-1]; // TODO: UGLY. HACK.
			$val = $particle['name'];
			//echo $val;
			
	}

	$description = get_lang_key($a['description_string'], array(1=>$val));
	str_replace('+-', '-',soft_nl2br($description));
	return array('desc' => $description,'type' => $a['effect_type']);
}
function int_to_effect($int)
{
	global $schema, $EFFECT_NAME_CACHE;
	if($int == -1)
		return "No effect";
		
	if(!$EFFECT_NAME_CACHE[$int])
	{
		foreach($schema['particles'] as $p)
		{
			if($p['id'] == $int)
			{
				$EFFECT_NAME_CACHE[$int] = $p['name'];
				return $EFFECT_NAME_CACHE[$int];
			}
		}
	}
	
	return $EFFECT_NAME_CACHE[$int];
}
function get_attribute($aid)
{
	global $schema;
	foreach($schema['attributes'] as $id => $a)
	{
		if($a['name'] == $aid)
		return $a;
	}
	return false;
}
function slot_to_label($slot)
{
	if($slot == 'pda')
		return $slot;
	if($slot == 'pda2')
		return 'Watch';
	return ucfirst($slot);
}
function get_crate_tooltip($series)
{
	global $CRATE_CONTENTS, $schema;
	$contents = $CRATE_CONTENTS[$series];
	if($contents)
	{
		foreach($contents as $c)
			if($c == CRATE_SPECIAL_ITEM)
				$items[] = '<span class="positive">Or an Exceedingly Rare Item!</span>';
			else
				$items[] = htmlspecialchars(get_item_name($c));
		
		return sprintf('<br /><span class="grey">%s</span>',implode($items,'<br />'));
	
	}
	
}
function get_paint_by_color($color)
{
	global $PAINT_CACHE, $schema;
	if($PAINT_CACHE[$color])
		return 	$PAINT_CACHE[$color];
		
	foreach($schema['items'] as $i)
	{
		if($i['attributes'])
			foreach($i['attributes'] as $a)
			{
				if($a['class'] == 'set_item_tint_rgb' && $a['value'] == $color)
				{
					$id = $i['defindex'];
					$PAINT_CACHE[$color] = $id;
					return $id;
				}
			}	
	}
	return 0;
}
function equipped_convert($equipped)
{
	$ret = array();
	foreach($equipped as $e)
	{
		$ret[$e['class']] = $e['slot'];	
	}
	return $ret;
}
function int_to_origin($int)
{
	global $schema;
	foreach($schema['origins'] as $o)
		if($o['origin'] == $int)
			return $o['name'];
	
	return 'Unknown ('.$int.')';
}
?>