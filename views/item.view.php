<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class item_view extends view
{
	public function prepare()
	{
		global $schema, $asset_info, $settings;
		$this->template="item";
		$this->tab = 'item';
		
		$b = new backpack(false);

		$item_stats = cache::Memcached()->get('item_stats');
		if( $item_stats === false )
		{
			$json = file_get_contents($settings['cache']['folder'].'item_stats.json');
			$item_stats = json_decode($json, true);
			cache::Memcached()->set('item_stats', $item_stats);
		}
		
		cache::inc('tf2_item_info.php');
		
		$defindex = $this->request[1];
		$url_name = $this->request[0];
		
		$s = $item_stats['items'][$defindex];
		$si = $schema['items'][$defindex];
		$info = $asset_info[$defindex];
		
		//var_dump($info);
		
		$i = $b->get_item($si);
		$this->canonical = sprintf('http://tf2stats.net/item/%s/%s/',$i['name_url'],$i['defindex']);
		
		$i['owned'] = ($s['unique_total'] / $item_stats['total_players']);

		$i['equipped'] = ($s['total_equipped'] / $item_stats['total_players']); 
		$i['owned_equipped'] = $s['unique_total'] > 0 ? ($s['total_equipped'] / $s['unique_total']) : 0;
		
		// Colors
		if( !empty( $s['colors']) )
		{
			arsort($s['colors']);

			foreach($s['colors'] as $c => $num)
			{
				$id = get_paint_by_color($c);

				$si = $schema['items'][$id];
				$it = $b->get_item($si);
				$name = $it['name'];
				if(strlen($name) > 25)
					$name = substr($name,0,25)."...";
				//var_dump($it);

				$html = dechex($c);
				if($html === '0')
					continue;//$html = '914c3f';
				$key = $num;
				while($i['colors'][$key] > 0)
					$key++;
				$i['colors'][$key] = array('name' => $name, 'color' => $html, 'num' => ($num/$s['total_colored']));
			}
		}
		
		
		// qualities
		if( !empty( $s['qualities'] ) )
		{
			arsort($s['qualities']);
			foreach($s['qualities'] as $c => $num)
			{
				$key = $num;
				while($i['qualities'][$key] > 0)
					$key++;

				$quality = int_to_quality($c);
				$label = quality_to_label($quality);
				//$color = quality_to_color($quality);
				$i['qualities'][$key] = array('color' => $color, 'num' => ($num/$s['total']), 'label' => $label, 'quality' => $quality);
			}
		}

		// Origins
		if( !empty( $s['origins'] ) )
		{
			arsort($s['origins']);
			foreach($s['origins'] as $c => $num)
			{
				$key = $num;
				while($i['origins'][$key] > 0)
					$key++;

				$label = int_to_origin($c);
				//$color = quality_to_color($quality);
				$i['origins'][$key] = array('num' => ($num/$s['total']), 'label' => $label);
			}
			arsort($s['effects']);
		}
	
		// effects
		if( !empty( $s['effects']) )
		{
			$total_effects = 0;
			foreach($s['effects'] as $c => $num)
				$total_effects += $num;

			foreach($s['effects'] as $c => $num)
			{

				$t = $s['total_effect'] - $s['effects']['-1'];
				if($c == -1)
					continue;
				$key = $num;
				while($i['effects'][$key] > 0)
					$key++;

				$label = int_to_effect($c);
				$i['effects'][$key] = array('color' => $color, 'num' => ($num/$total_effects), 'label' => $label);
			}
		}

		if( !empty( $i['used_by_classes'] ) )
		{
			if(count($i['used_by_classes']['class']) < 1)
			for($c=0;$c<9;$c++)
			{
				$num = $s['equipped'][$c];
				if($s['total_equipped'] > 0)
					$i['classes'][] = array('class' => int_to_class($c), 'id' => $c, 'num' => ($num/$s['total_equipped']));
			}
		}
			
		$this->title = sprintf("%s statistics",htmlspecialchars($i['name']));
		$this->params['item'] = $i;
		$this->params['url_name'] = $url_name;
		$this->params['stats'] = $s;
		$this->params['total'] =  $item_stats['total_players'];
		$this->params['info'] = $info;
		$this->params['debug'] = $debug;
	
	
	}
}

?>