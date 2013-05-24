<?php 
require_once('classes/view.php');

class items_view extends view
{
	public function prepare()
	{
		$this->template="items";
		$this->tab = 'item';
		$this->title = "Item statistics";
	}
}

?>