<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class misc_items_view extends view
{
	public function prepare()
	{
		global $schema, $settings;
		$this->template="cosmetic";
		$this->tab = 'item';
		$this->title = 'Cosmetic item statistics';
		$this->canonical = 'http://tf2stats.net/misc_items/';
		
		$sort_table = array(
			'owned' => 'total',
			'equipped' => 'total_equipped',
			'own_equip' => 'owned_equipped'
		);
		
		$b = new backpack(false);
		
		$sortkey = 'total';
		if($this->request['sort'] && array_key_exists($this->request['sort'],$sort_table))
		{
			$sortkey = $sort_table[$this->request['sort']];
			$this->params['sort'][$this->request['sort']] = 'selected';
		} else 
			$this->params['sort']['owned'] = 'selected';

		$item_stats = cache::Memcached()->get('item_stats');
		if( $item_stats === false )
		{
			$json = file_get_contents($settings['cache']['folder'].'item_stats.json');
			$item_stats = json_decode($json, true);
			cache::Memcached()->set('item_stats', $item_stats, time() + 60*15);
		}
		
		foreach($item_stats['items'] as $defindex => $s)
		{
			if(in_array($defindex,$settings['items']['ignore']))
				continue;
			$si = $schema['items'][$defindex];
			
			if($si['item_slot'] == 'misc' /*|| in_array($si['defindex'], $settings['items']['cosmetic'])*/)
			{
				$i = $b->get_item($si);
				$i['owned'] = ($s['total'] / $item_stats['total_players']);
				if($i['owned'] > 1)
					$i['owned'] = 1; 
				$i['equipped'] = ($s['total_equipped'] / $item_stats['total_players']); 
				$i['owned_equipped'] = ($s['total_equipped'] /$s['total']); 
				$s['owned_equipped'] = intval($s['total_equipped'] / $s['total'] * 1000);
				
				// Colors
				
				arsort($s['colors']);
				
				foreach($s['colors'] as $c => $num)
				{
					$html = dechex($c);
					if($html === '0')
						continue;//$html = '914c3f';
					$key = $num;
					while($i['colors'][$key] > 0)
						$key++;
					$i['colors'][$key] = array('color' => $html, 'num' => ($num/$s['total_colored']));
				}	
			

				if(count($i['used_by_classes']['class']) < 1)
					for($c=1;$c<=9;$c++)
					{
						$num = $s['equipped'][$c];
						if($s['total_equipped'] > 0)
							$i['classes'][] = array('class' => int_to_class($c), 'id' => $c, 'num' => ($num/$s['total_equipped']));
					}

				$key = $s[$sortkey];
				while($this->params['hats'][$key] > 0)
					$key++;
				$this->params['hats'][$key] = $i;
			}
		}
		krsort($this->params['hats']);
	}
}

?>