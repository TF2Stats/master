<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class economy_view extends view
{
	public function prepare()
	{
		global $schema, $settings;
		$this->template="economy";
		$this->tab = 'item';
		$this->title = "State of the economy";
		
		$b = new backpack(false);
		
		$json = cache::read('item_stats.json');
		$item_stats = json_decode($json, true);
		
		$total_items = $item_stats['total_items'];
		
		$metal_index = $item_stats['total_metal'] / $item_stats['total_players'];
		
		$this->params['metal_index'] = $metal_index;
		krsort($this->params['effects']);
		$this->params['profiles'] = $item_stats['total_players'];
		$this->params['time'] = cache::date('item_stats.json');
	}
}

?>