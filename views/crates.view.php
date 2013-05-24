<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class crates_view extends view
{
	public function prepare()
	{
		global $schema;
		$this->template="crates";
		$this->tab = 'item';
		
		$b = new backpack(false);
		
		$json = cache::read('item_stats_luigi.json');
		$crate_stats = json_decode($json, true);
		
		foreach($crate_stats['crate_items'] as $series => $items)
		{
			foreach($items as $defindex => $drops)
			{
				$si = $schema['items'][$defindex];
				$i = $b->get_item($si);
				//var_dump($i);
				$i['drop_rate'] = $drops / $crate_stats['crate_totals'][$series];
				$crates[$series]['items'][] = $i;
				$crates[$series]['series'] = $series;
				$crates[$series]['sample'] = $crate_stats['crate_totals'][$series];
			}
		}
		
		
		$this->title = "Crate statistics";
		$this->params['crates'] = $crates;
		$this->params['stats'] = $s;
	
	
	}
}

?>