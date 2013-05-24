<?php 
/* Map object
 * 
 */

require_once('libs/bbcode/bbcode.php');

class map
{
	function __construct($map_name)
	{
		global $db;
		$mi = $db->query_first("SELECT id, plays, official, first_seen, score, rank, players, servers FROM tf2_maps WHERE name=%s",array($map_name));
		$mi['name'] = $map_name;
		
		$this->info = $mi;
		
		$this->map = $map_name;
		$this->map_id = $mi['id'];
	}
	
	function get_info()
	{
		return $this->info;
	}
	
	function get_servers($limit=25)
	{
		global $db;
		$db->query("SELECT s.name, s.ip, s.port, sm.plays, sm.players, s.max_players, sm.time FROM tf2_server_maps sm
				LEFT JOIN tf2_servers s on s.id = sm.server_id WHERE sm.map_id=%s ORDER BY sm.time DESC, sm.players DESC LIMIT ".mysql_real_escape_string($limit),array($this->map_id));
		while($row = $db->fetch_array())
		{
			$row['url'] = sprintf("/server/%s:%s/",$row['ip'],$row['port']);
			$rows[] = $row;
		}
		return $rows;
	}
	function get_extra()
	{
		global $db;
		$i = $db->query_first("SELECT p.name, p.id64, i.edit_time, i.description, i.file_size, i.download_url FROM tf2stats_managed_maps i LEFT JOIN tf2_players p ON i.player_id=p.id WHERE map_id=%s ORDER BY edit_time DESC LIMIT 1",array($this->map_id));
		if($i['description'])
		{
			$parser = new ubbParser();
			$i['description'] = $parser->parse($i['description']);
		}
		return $i;
	}
	function get_authors()
	{
		global $db;
		$db->query("SELECT p.name, mp.type from tf2stats_map_to_player mp LEFT JOIN tf2_players p ON mp.player_id = p.id WHERE mp.map_id = %s AND type = 'A'",array($this->map_id));
		while($row = $db->fetch_array())
			$p[] = $row;
		return $p;
	}
	function get_history()
	{
		global $db;
		$db->query("SELECT time, servers, players, slots FROM tf2_map_history WHERE map_id=%s",array($this->map_id));
		while($row = $db->fetch_array())
			$rows[]=$row;
			
		return $rows;
	}
	
	function get_stats($period = 1209600) // default: 2 weeks
	{
		global $db;

	}
	function get_images($param='xy100')
	{
		global $settings,$db;
		$images = array();
		$db->query("SELECT id, image, name FROM tf2stats_map_images i LEFT JOIN tf2_players p on i.player_id = p.id WHERE map_id = %s ORDER BY pri DESC",array($this->map_id));
		while($im = $db->fetch_array())
		{
			if($im)
			{
				//$thumb = sprintf('%smaps/%s/%s',$settings['upload']['filter_url'],$im['image'],$param);
				$thumb = sprintf('%simages/maps/sized/%s/%s',$settings['static_folder'],$param,$im['image']);
				$full = sprintf('%s/%s',$settings['upload']['original_ext']['maps'],$im['image']);
				$del_link = sprintf('/manage_map/%s/delimg/%s/',$this->map,$im['image']);
				$images[] = array('image' => $thumb, 'full' => $full, uploader=> $im['name'], 'name' => $this->info['name'], 'del_link' => $del_link);
			}
		}
		
		return $images;//sprintf('%simages/default_maps_small.png',$settings['static_folder']);
	}
	
	static function get_map_list($args = array())
	{
		global $db;
		$defaults = array (
			'funcs' => array( 
				'count' => array ('func' => 'count', 'param' => '%02d'),
				'link' => array ( 'func' => 'link'),
				'label' => array ( 'func' => 'label')
			),
			'query' => 'SELECT * FROM tf2_maps LIMIT 10;'
			);
		$s = extend($defaults, $args);
		
		$mlq = $db->query($s['query']);
		while($row = $db->fetch_array($mlq))
		{
			$cols = array();
			foreach($s['funcs'] as $key => $f)
			{
				$val = map_functions::$f['func']($f['param'],$row);
				$row[$key] = $val;
			}
			$rows[] = $row;
		}
		return $rows;
	}
}

class map_functions
{
	static function count($param='%d', $row)
	{
		global $_MAP_COUNT;
		return sprintf($param,++$_MAP_COUNT);
	}
	static function label($param='', $row)
	{
		global $settings;
		$n = $row['name'];
		if($param && strlen($n) > $param+3)
		{
			$n = substr($n,0,$param).'...';
		}
		
		if($row['official'])
			$o = sprintf('<img src="%simages/icon_tf2.png" alt="Official" /> ',$settings['static_folder']);
		return sprintf('%s%s',$o,$n);
	}
	static function icon($param='', $row)
	{
		global $settings;
		if($row['official'])
			return sprintf('<img src="%simages/icon_tf2.png" alt="Official" />',$settings['static_folder']);
	}
	static function link($param='', $row)
	{
		return sprintf('/map/%s',$row['name']);
	}
	static function image($param='xy165', $row)
	{
		global $settings,$db;
		$im = $db->query_first("SELECT image FROM tf2stats_map_images WHERE map_id = %s ORDER BY pri DESC LIMIT 1",array($row['id']));
		if($im)
		{
			$image = sprintf('%simages/maps/sized/%s/%s',$settings['static_folder'],$param,$im['image']);
			return $image;
		}
		return sprintf('%simages/maps/sized/%s/default_maps_small.png',$settings['static_folder'],$param);
	}
	static function type($param='',$row)
	{
		global $settings;
		
		$bits = explode( '_', $row['name'], 2 );
		$type = $bits[ 0 ];
		
		if( isset( $settings['map_type_lookup'][ $type ] ) )
		{
			return $settings['map_type_lookup'][ $type ];
		}
		
		return 'Unknown type';
	}
}
?>