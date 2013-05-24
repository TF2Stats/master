<?php 
require_once('classes/view.php');

class server_view extends view
{
	public function prepare()
	{
		$this->template="server";
		$this->tab = 'server';
		require_once('classes/server.php');

		$server = new server($this->request[0]);
		$this->params['image'] = $server->get_map_image();
		$this->params['info'] = $server->info;
		$this->params['map_list'] = $server->get_map_list();
		$this->params['rule_list'] = $server->get_rule_list();
		
		$this->title = htmlspecialchars($this->params['info']['name']);
	}
}

?>