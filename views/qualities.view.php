<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class qualities_view extends view
{
	public function prepare()
	{
		global $schema, $settings;
		$this->template="qualities";
		$this->tab = 'item';
		$this->title = "Quality statistics";
		
		$b = new backpack(false);
		
		$json = cache::read('item_stats.json');
		$item_stats = json_decode($json, true);
		
		$total_qualities = $item_stats['qualities_total'];
		$total_items = $item_stats['total_items'];
		
		$qualities = array();
		
		foreach($item_stats['qualities'] as $c => $p)
		{

			$qualities[$p] = array( 
				'total' => $p / $total_items,
				'quality' => $c,
				'name' => quality_to_label(int_to_quality($c)),
				'color' => quality_to_color(int_to_quality($c)),
				'tooltip' => sprintf('<h1 class="%s">%s</h1>',int_to_quality($c),quality_to_label(int_to_quality($c)))
			);
		}
		$this->params['qualities'] = $qualities;
		krsort($this->params['qualities']);
		//var_dump($this->params['qualities']);
		$this->params['profiles'] = $item_stats['total_players'];
		$this->params['time'] = cache::date('item_stats.json');
	}
}

?>