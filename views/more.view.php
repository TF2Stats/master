<?php 
require_once('classes/view.php');

class more_view extends view
{
	public function prepare()
	{
		$this->template="more";
		$this->tab="more";
		$this->title = 'More goodies';
	}
}

?>