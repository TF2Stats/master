<?php 

class server
{
	public $info;
	function __construct($address)
	{
		global $db;
		if(strpos($address,':') !== false)
		{
			list($ip, $port) = explode(":",$address);
			$server = $db->query_first("SELECT s.id, s.ip, s.port, s.name, s.map_id, m.official, m.name as map_name, s.players, s.bots, s.max_players, s.password, s.secure, s.tags, s.last_seen
			FROM tf2_servers s LEFT JOIN tf2_maps m ON s.map_id = m.id WHERE ip=%s AND port=%s", array($ip,$port));
		}
		else
			$server = $db->query_first("SELECT s.id, s.ip, s.port, s.name, s.map_id, m.name as map_name, s.players, s.bots, s.max_players, s.password, s.secure, s.tags, s.last_seen
			FROM tf2_servers s LEFT JOIN tf2_maps m ON s.map_id = m.id WHERE s.id=%s",array($address));
		$server['map_label'] = server_functions::map_name('', $server);
		$this->info = $server;
	}
	function get_map_image($size='x128.y96')
	{
		global $db, $settings;
		
		$im = $db->query_first("SELECT image FROM tf2stats_map_images WHERE map_id = %s ORDER BY pri DESC LIMIT 1",array($this->info['map_id']));
		if(!$im)
		{
			$im = array();
			$im['image'] = 'default_maps_small.png';
		}
		$thumb = sprintf('%simages/maps/sized/%s/%s',$settings['static_folder'],$size,$im['image']);
		$full = sprintf('%s/%s',$settings['upload']['original_ext']['maps'],$im['image']);
		$image = array('thumb' => $thumb, 'full' => $full);
		return $image;
	}
	function get_rule_list()
	{
		global $db, $settings;
		
		$db->query("SELECT k.name, r.value FROM tf2_server_rules r LEFT JOIN tf2_keys k ON r.key_id = k.id WHERE r.server_id = %s AND r.time > %s",array($this->info['id'], time()-$settings['server']['rule_length']));
		while($row = $db->fetch_array())
		{
			if($settings['server']['rule_ignore'][$row['name']] == $row['value'] || $settings['server']['rule_ignore'][$row['name']] == 'ALL')
				continue;
			$rules[] = $row;
		}
		
		return $rules;
	}
	function get_map_list($limit=50)
	{
		global $db;
		$rows = $db->query("SELECT m.name, sm.time, sm.plays, sm.players FROM tf2_server_maps sm LEFT JOIN tf2_maps m ON sm.map_id = m.id WHERE sm.server_id = %s ORDER BY sm.time DESC LIMIT ".mysql_real_escape_string($limit),
				array($this->info['id']));
		while($row = $db->fetch_array())
			$maps[] = $row;
			
		return $maps;
	}
	
	public static function get_server_list($args = array())
	{
		global $db;
		$defaults = array (
			'funcs' => array( 
				'count' => array ('func' => 'count', 'param' => '%02d'),
				'link' => array ( 'func' => 'link'),
				'label' => array ( 'func' => 'label')
				
			),
			'query' => 'SELECT * FROM tf2_servers LIMIT 10;'
			);
		$s = extend($defaults, $args);
		
		$mlq = $db->query($s['query']);
		while($row = $db->fetch_array($mlq))
		{
			$cols = array();
			foreach($s['funcs'] as $key => $f)
			{
				$val = server_functions::$f['func']($f['param'],$row);
				$row[$key] = $val;
			}
			$rows[] = $row;
		}
		return $rows;
	}
	
}

class server_functions
{
	static function count($param='%d', $row)
	{
		global $_SERVER_COUNT;
		return sprintf($param,++$_SERVER_COUNT);
	}
	static function label($param='', $row)
	{
		global $settings;
		return sprintf('%s',$row['name']);
	}
	static function link($param='', $row)
	{
		return sprintf('/server/%s:%s',$row['ip'],$row['port']);
	}
	static function player_label($param='%d/%d',$row)
	{
		return sprintf($param,$row['players'],$row['max_players']);
	}
	static function image($param='xy64', $row)
	{
		global $settings,$db;
		$im = $db->query_first("SELECT image FROM tf2stats_map_images WHERE map_id = %s ORDER BY pri DESC LIMIT 1",array($row['map_id']));
		if($im)
		{
			$image = sprintf('%smaps/%s/%s',$settings['upload']['filter_url'],$im['image'],$param);
			return $image;
		}
		return sprintf('%simages/default_maps_small.png',$settings['static_folder']);
	}
	static function map_name($param='', $row)
	{
		require_once('map.php');
		$row['name'] = $row['map_name'];
		return map_functions::label($param,$row);
	}
}

?>