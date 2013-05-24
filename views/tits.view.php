<?php 
require_once('classes/view.php');

class tits_view extends view
{
	public function prepare()
	{
		$this->template="tits";
		$this->tab="more";
		$this->title = 'OH MY GOD, TITS!';
	}
}

?>