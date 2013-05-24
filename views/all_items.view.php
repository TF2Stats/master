<?php 
require_once('classes/view.php');
require_once('classes/backpack.php');
		
class all_items_view extends view
{
	public function prepare()
	{
		global $schema;
		$this->template="all_items";
		$this->tab = "all_items";
		$this->title = "All TF2 Items";
		
		$b = new backpack(false);
		
		foreach($schema['items'] as $i)
		{
			$items[] = $b->get_item($i);
		}
		$this->params['items'] = $items;
	}
}

?>