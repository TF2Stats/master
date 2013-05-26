<?php 
class servers_view extends view
{
	public function prepare()
	{
		global $db, $settings;
		
		$this->template="servers";
		$this->tab = 'server';
		$this->title = "Server search";

		$server_stats = cache::Memcached()->get('server_stats');
		if( $server_stats === false )
		{
			$json = file_get_contents($settings['cache']['folder'].'server_stats.json');
			$server_stats = json_decode($json, true);
			cache::Memcached()->set('server_stats', $server_stats, time() + 60*15);
		}
		
		$this->params['stats'] = $server_stats;
	}
}
?>