<?php 
class map_manager_view extends view
{
	public function prepare()
	{
		global $db, $user;
		
		$this->template="map_manager";
		$this->title = "Map Manager";
		
		$type_lookup = array(
			'M' => 'Ma',
			'A' => 'Au',
			'C' => 'Co',
		);

		if($_REQUEST['request'] && $_REQUEST['map'])
		{
			$map = $db->query_first("SELECT id FROM tf2_maps WHERE name=%s",array($_REQUEST['map']));
			if(!$map['id'])
			{
				$this->params['error'] = 'TF2Stats has never seen '.htmlspecialchars($_REQUEST['map']).' before.';
			} else {
				$db->query("INSERT INTO tf2stats_map_to_player(player_id, map_id, type) VALUES(%s, %s, %s)",array(
					$user->id(), $map['id'],$_REQUEST['type']
				));
				$this->params['success'] = htmlspecialchars($_REQUEST['map']).' has been added to your map list.';
			}
		}
		
		$db->query("SELECT m.name, m.id, mp.type FROM  tf2stats_map_to_player mp LEFT JOIN tf2_maps m ON m.id = mp.map_id
						WHERE mp.player_id = %s", array($user->id()));
		while($row = $db->fetch_array())
		{
			$m = array (
				'label' => $row['name'],
				'link' => sprintf('/manage_map/%s/',$row['name']),
				'type' => $row['type'] //$type_lookup[$row['type']]
			);
			$managed_maps[] = $m;
		}
		//var_dump($managed_maps);
		$this->params['managed_maps'] = $managed_maps;
	}
}
?>