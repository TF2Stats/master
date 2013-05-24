<?php 
require_once('classes/player.php');

class scrape_view extends view
{
	public function prepare()
	{
		$this->template="player_stats";
		$this->tab="player";
		
		//backpack::update_schema();
		if(is_id64($this->request[0]))
			$player_id = $this->request[0];
		else
		{
			$player_id = get_id64($this->request[0]);
			if(!$player_id)
				die("404");
		}
		
		$player = new player($player_id);
		
		die("302");
	}
}
?>