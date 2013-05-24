<?php 
require_once('classes/view.php');
require_once('classes/map.php');

class map_list_view extends view
{
	public function prepare()
	{
		global $SITE, $db;
		$this->template="map_list";
		$this->tab = 'map';
		$this->title = 'Map list';

		$page_size = 50;
		
		$page = mysql_real_escape_string((int)$this->request[1]);
		$method = $this->request[0];
		$query = '';
		
		switch($method)
		{
			case 'rank':
			case 'score':
			default:
				$method = 'score';
				$query = sprintf('SELECT id, name, official, score, rank, players, servers, plays FROM tf2_maps ORDER BY score DESC LIMIT '.$page*$page_size.', '.$page_size);
		}

		$this->params['maps'] = map::get_map_list(
			array( 
				'funcs' => array( 
					'link' => array ( 'func' => 'link'),
					'label' => array ( 'func' => 'label'),
					'type' => array ( 'func' => 'type'),
					'icon' => array ( 'func' => 'icon'),
					'image' => array ( 'func' => 'image', 'param' => 'x128.y96')
				),
				'query' => $query
			)
		);
		$this->params['method'] = $method;
		$this->params['page'] = $page;
		
	}
}

?>