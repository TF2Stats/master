<?php 
require_once('classes/view.php');
require_once('classes/map.php');

class maps_view extends view
{
	public function prepare()
	{
		global $SITE, $db;
		$this->template="maps";
		$this->tab = 'map';
		$this->title = 'Popular maps';
		
		$f = $db->query_first("SELECT id, name, rank, score, plays, players FROM tf2_vars v INNER JOIN tf2_maps m on v.value = m.id");
		$fa = array (
			'image' => map_functions::image('x600.y360', $f),
			'label' => map_functions::label('', $f),
			'link' => map_functions::link('', $f),
			'type' => map_functions::type('',$f)
		);
		$mlen = 18;
		$f['display_name'] = (strlen($f['name']) > $mlen) ? (substr($f['name'],0,$mlen-3).'...') : $f['name'];
		
		$this->params['feat'] = array_merge($f,$fa);
		///////////////////////////////////////// New maps ////////////////////////////////////
		$this->params['new_maps'] = map::get_map_list(
			array( 
				'funcs' => array( 
					'count' => array ('func' => 'count', 'param' => '%02d'),
					'link' => array ( 'func' => 'link'),
					'label' => array ( 'func' => 'label', 'param' => 25)
				),
				'query' => 'SELECT * FROM tf2_maps ORDER BY first_seen DESC LIMIT 10;'
			)
		);
		
		//////////////////////////////////////// popular maps ///////////////////////////////////
		$this->params['popular_maps'] = map::get_map_list(
			array( 
				'funcs' => array( 
					'link' => array ( 'func' => 'link'),
					'label' => array ( 'func' => 'label'),
					'image' => array ( 'func' => 'image', 'param' => 'xy165')
				),
				'query' => sprintf('SELECT id,name, official FROM tf2_maps ORDER BY score DESC LIMIT 10',$time)
			)
		);
		
	}
}

?>