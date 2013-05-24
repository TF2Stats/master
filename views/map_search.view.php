<?php 
require_once('classes/view.php');

class map_search_view extends view
{
	public function prepare()
	{
		global $db;
		$this->template="map_search";
		
		require_once('classes/map.php');
	
		$q = $this->request['search'];
		//$db->debug=true;
		$db->query("SELECT name FROM tf2_maps WHERE name LIKE %s",array(sprintf("%%%%%s%%%%",$q)));
		//echo mysql_error();
		if($db->num_rows() == 1)
		{
			$row = $db->fetch_array();
			//die();
			header('Location: /map/'.$row['name']);
			exit();
		}
		if(!$db->num_rows())
		{
			page::error("We searched and searched","But we just couldn't find the map you were looking for.
				Rest assured Heavy is on the job. He won't rest until he knows who's been tampering with his gun.
				<br/><br/>That's what you were searching for, right?",array('image' => "heavy_yell"));
		}
		//$db->debug=true;
		$this->params['list'] = map::get_map_list(
			array( 
				'funcs' => array( 
					'link' => array ( 'func' => 'link'),
					'label' => array ( 'func' => 'label'),
				),
				'query' => sprintf('SELECT m.id, m.name, m.official, m.score, plays, servers, first_seen FROM tf2_maps m WHERE name LIKE \'%%%s%%\' ORDER BY score DESC LIMIT 50',$q)
			)
		);
		$this->title = sprintf("Search: %s",htmlspecialchars($this->request[0]));
		
	}
}

?>