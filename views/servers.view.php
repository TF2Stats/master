<?php 
class servers_view extends view
{
	public function prepare()
	{
		global $db;
		
		$this->template="servers";
		$this->tab = 'server';
		$this->title = "Server search";
		
		$json = cache::read('server_stats.json');
		$server_stats = json_decode($json, true);
		
		$this->params['stats'] = $server_stats;
	}
}
?>