<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class paints_view extends view
{
	public function prepare()
	{
		global $schema, $settings;
		$this->template="paints";
		$this->tab = 'item';
		$this->title = "Paint statistics";
		
		$sort_table = array(
			'total' => 'painted',
			'owned' => 'owned',
			'used' => 'usage'
		);
		
		$b = new backpack(false);
		
		$sortkey = 'painted';
		if($this->request['sort'] && array_key_exists($this->request['sort'],$sort_table))
		{
			$sortkey = $sort_table[$this->request['sort']];
			$this->params['sort'][$this->request['sort']] = 'selected';
		} else 
			$this->params['sort']['total'] = 'selected';


		$item_stats = cache::Memcached()->get('item_stats');
		if( $item_stats === false )
		{
			$json = file_get_contents($settings['cache']['folder'].'item_stats.json');
			$item_stats = json_decode($json, true);
			cache::Memcached()->set('item_stats', $item_stats, time() + 60*15);
		}
		
		$total_paints = $item_stats['total_colors'];
		$total_items = $item_stats['total_items'];
		
		foreach($item_stats['colors'] as $c => $p)
		{
			$id = get_paint_by_color($c);
			if(!$id)
				continue;
			//printf("Color: %s, id: %s <br />",$c, $id);
			$s = $item_stats['items'][$id];
			
			$si = $schema['items'][$id];
			
			$i = $b->get_item($si);
			$owned = ($s['total'] / $item_stats['total_players']);
			
			//printf("Owned: %s, Total: %s<br>", $s['total'], $item_stats['total_players']);
			
			$paint = array(
				'total' => $p,
				'color' => $c,
				'painted' => $p / $total_paints,
				'owned' => (($s['total'] + $p) / $item_stats['total_players']),
				'usage' => $p / ($s['total'] + $p ) 
			);
			$key = (string)intval($paint[$sortkey]* 10000);
			while($paints[$key] > 0)
				$key++;
			//printf("%s -> %s: %s<br />",$paint[$sortkey], $key, $i['name']);
				
			$paints[$key] = array_merge($paint,$i);
		}
		$this->params['paints'] = $paints;
		krsort($this->params['paints']);
		$this->params['profiles'] = $item_stats['total_players'];
		$this->params['time'] = cache::date('item_stats.json');
	}
}

?>