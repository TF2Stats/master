<?php 

class server_search_view extends view
{
	public function prepare()
	{
		global $db;
		$this->template="server_search";
		$this->tab = 'server';
		require_once('classes/server.php');
		
		if($this->request['search'])
		{
			$req = $this->request['search'];
			if(strpos($req,':'))
			{
				list($ip,$port) = explode(":",$req);
				$wheres[] = '(ip = %s AND port = %s)';
				$whargs[] = $ip;
				$whargs[] = $port;	
			} else {
				$wheres[] = 'ip LIKE %s';
				$whargs[] = '%'.$req.'%';
			}
			// name search
			$wheres[] = 's.name LIKE %s';
			$whargs[] = '%'.$req.'%';
			
			// tag search
			$wheres[] = 'tags LIKE %s';
			$whargs[] = '%'.$req.'%';
			
			$where = implode(' OR ',$wheres);
			
			$q = $this->request['search'];
			$db->query("SELECT ip, port FROM tf2_servers s WHERE ".$where,$whargs);
			//echo mysql_error();
			if($db->num_rows() == 1)
			{
				$row = $db->fetch_array();
				header(sprintf('Location: /server/%s:%s',$row['ip'],$row['port']));
				exit();
			}
			if(!$db->num_rows())
			{
				page::error("Spy sappin my search results","Sorry to <i>pop in</i> unannounced, but that server you were looking for?
				It's nowhere to be found. I'm not saying one of my sappers didn't have anything to do with it, but mind the charred
				bits of computer on the floor.",array('image' => "spy"));
			}
			//$db->debug=true;
			
			foreach($whargs as $wh)
				$cleanargs[] = "'".mysql_real_escape_string($wh)."'";

			$query = vsprintf('SELECT s.ip, s.port, s.name, s.map_id, m.name AS map_name, s.players, s.max_players, bots FROM tf2_servers s LEFT JOIN tf2_maps m ON s.map_id=m.id WHERE '.$where.' LIMIT 50',$cleanargs);
			
			$this->params['list'] = server::get_server_list(
				array( 
					'funcs' => array( 
						'link' => array ( 'func' => 'link'),
						'label' => array ( 'func' => 'label'),
						'player_label' => array ( 'func' => 'player_label', 'param' => '%d/%d')
					),
					'query' => $query
				)
			);
			$this->title = sprintf("Search: %s",htmlspecialchars($this->request[0]));
		}
	}
}
?>