<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class effects_view extends view
{
	public function prepare()
	{
		global $schema, $settings;
		$this->template="effects";
		$this->tab = 'item';
		$this->title = "Effect statistics";
		
		$b = new backpack(false);

		$item_stats = cache::Memcached()->get('item_stats');
		if( $item_stats === false )
		{
			$json = file_get_contents($settings['cache']['folder'].'item_stats.json');
			$item_stats = json_decode($json, true);
			cache::Memcached()->set('item_stats', $item_stats);
		}

		$total_effects = $item_stats['total_effects'];
		$total_items = $item_stats['total_items'];
		
		foreach($item_stats['effects'] as $c => $p)
		{
			$c = (int)$c; // I don't even
			
			//if($c && $c > 0)
			//{
				$name = int_to_effect($c);
				$class = 'rarity4';
				if($c == 4)
					$class = 'community';
				if($c == 2)
					$class = 'developer';
				
				$img = sprintf("%sxy78/%d.png",$settings['upload']['resized_ext']['effects'],$c);
				
				if( !file_exists( $settings['upload']['folder']['effects'] . $c . '.png' ) )
				{
					$img = '/static/images/items/sized/xy78/unknown.png';
				}
				
				$i = $p;
				while($effects[(string)$i])
					$i+=0.01;
				$effects[(string)$i] = array(
					'total' => $p / $total_effects,
					'global' => $p / $total_items,
					'players' => $p / $item_stats['total_players'],
					'effect' => $c,
					'image' => $img,
					'name' => $name,
					'tooltip' => sprintf('<h1 class="%s">%s</h1>',$class,$name),
					'raw' => $p,
					'sort' => $i
				);	
			//}
		}
		$this->params['effects'] = $effects;
		krsort($this->params['effects']);
		$this->params['profiles'] = $item_stats['total_players'];
		$this->params['time'] = cache::date('item_stats.json');
	}
}

?>