<?php 
require_once('classes/view.php');

class map_view extends view
{
	public function prepare()
	{
		$this->template="map";
		$this->tab = 'map';
		global $SITE;
		$SITE['head'] .= '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
		require_once('classes/map.php');

		$map = new map($this->request[0]);
		
		$i =  $map->get_info();
		$s = $map->get_stats();
		$stats[] = array(	'key' => 'Players', 
							'now' => $i['players'],
							'average' => $s['avg_players']
						);
		$stats[] = array(	'key' => 'Servers', 
							'now' => $i['servers'],
							'average' => $s['avg_servers']
						);
		$stats[] = array(	'key' => 'Saturation', 
							'now' => ($i['players'] > 0) ? $i['servers']/$i['players'] : 0,
							'average' => $s['saturation']
						);
						

		
		$this->title = htmlspecialchars($this->request[0]);
		$this->params['info'] = $i;
		$this->params['servers'] = $map->get_servers(25);
		$this->params['images'] = $map->get_images();
		$this->params['extra'] = $map->get_extra();
		$this->params['stats'] = $stats;
	}
}

?>