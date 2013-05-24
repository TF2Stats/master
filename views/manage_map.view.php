<?php 
class manage_map_view extends view
{
	public function prepare()
	{
		global $db, $user, $settings;
		
		// auth check
		$auth = $db->query_first("SELECT mp.type FROM  tf2stats_map_to_player mp LEFT JOIN tf2_maps m ON m.id = mp.map_id
						WHERE mp.player_id = %s AND m.name = %s", array($user->id(), $this->request[0]));
		if(!in_array($auth['type'], array('M','A','C')))
			page::error("Little man","You are no match for me!");
		
		// handle file removals
		if($this->request[1] == 'delimg')
		{
			$db->query("DELETE FROM tf2stats_map_images WHERE image = %s",array($this->request[2]));
			$this->params['success'] = "Deleted ".$this->request[2];
		}
		
		// update	
		if($_REQUEST['update'])
		{
			if($_REQUEST['filesize'] && !is_numeric($_REQUEST['filesize']))
			{
				$this->params['error'] = 'Filesize must be numeric. Do not append "MB".';
			}
			elseif($_REQUEST['url'] && !filter_var($_REQUEST['url'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
			{
				$this->params['error'] = 'Download URL is not valid.';
			} else {
				$i = $db->query_first("SELECT m.id, m.official FROM tf2_maps m WHERE m.name = %s", array($this->request[0]));
				
				if( IsUserAdmin( ) )
				{
					$Official = (int)( isset( $_REQUEST[ 'official' ] ) && $_REQUEST[ 'official' ] == 'official' );
					
					cache::log( "Changing official status for " . $i['id'] . " - old: " . $i[ 'official' ] . " - new: "  . $Official );
					
					if( $i[ 'official' ] != $Official )
					{
						$db->query( "UPDATE tf2_maps SET official = %s WHERE id = %s", array( $Official, $i['id'] ) );
					}
				}
				
				$db->query("INSERT INTO tf2stats_managed_maps (player_id, map_id, edit_time, description, file_size, download_url) VALUES(%s, %s, %s, %s, %s, %s)
							ON DUPLICATE KEY UPDATE edit_time=%s, description=%s, file_size=%s, download_url = %s", array(
							$user->id(), $i['id'],
							time(), $_REQUEST['description'], $_REQUEST['filesize'], $_REQUEST['url'],
							time(), $_REQUEST['description'], $_REQUEST['filesize'], $_REQUEST['url']
				));
			}
		}
		
		// map info
		$map_info = $db->query_first("SELECT m.name, m.id, m.official, mp.description, mp.file_size, mp.download_url, mp.edit_time, p.name as player_name FROM  tf2stats_managed_maps mp 
						LEFT JOIN tf2_maps m ON m.id = mp.map_id
						LEFT JOIN tf2_players p on mp.player_id = p.id
						WHERE m.name = %s
						ORDER BY edit_time DESC
						LIMIT 1", array($this->request[0]));
		
		if($map_info)
			$this->params['old'] = true;
		else
			$map_info = $db->query_first("SELECT m.name,  m.id FROM tf2_maps m WHERE m.name = %s", array($this->request[0]));
		
		$this->params['map_info'] = $map_info;
		
		
		// handle adding authors.
		if($this->request[1] == 'addauthor')
		{
			if($this->request['search'])
			{
				$player_id = $this->request['search'];
				if(!is_id64($player_id))
					$player_id = get_id64($this->request['search']);
				$player = new player($player_id);
				if($player->id())
				{
					
					$db->query("INSERT INTO tf2stats_map_to_player(player_id, map_id, type) VALUES(%s, %s, %s)",array(
						$player->id(), $map_info['id'],'A'
					));
					$this->params['success'] = $player_id.' has been added to the author list.';
				} else
					$this->params['error'] = "Could not find a player by '".$_REQUEST['search']."'. Please refine your search.";
			} else
			{
				$this->template="manage_map_author";
				$this->title = sprintf("Adding author for %s",htmlspecialchars($map_info['name']));
				return;
			}
		}
		if($this->request[1] == 'delauthor')
		{
			$id = $this->request[2];
			$db->query("DELETE FROM tf2stats_map_to_player WHERE player_id=%s AND map_id = %s",array(
						$id, $map_info['id']
					));
			$this->params['success'] = "Deleted author";
		}
		// handle file uploads.
		if($this->request[1] == 'upload')
		{
			$this->template="manage_map_upload";
			$this->title = sprintf("Upload image for %s",htmlspecialchars($map_info['name']));
			$this->params['allowed_images'] = implode(', ',$settings['upload']['allowed_images']);
			if($_FILES['image'])
			{	
				if(!$_FILES['image']['tmp_name'])
				{
					$this->params['error'] = 'Upload failed. (This usually happens when you try to upload a file larger than 1MB!)';
					return;
				}
				// check extension.
				$ext = end(explode(".",strtolower($_FILES['image']['name'])));
				if (!in_array(
						$ext,
						$settings['upload']['allowed_images'])
					)
				{
					$this->params['error'] = 'Unsupported file extension '.$ext.'. Please convert your image to one of these formats: '.implode(', ',$settings['upload']['allowed_images']);
					return;
				}
				// rename if already exists
				$filename = sprintf( "%s_%s",$map_info['id'], str_replace( Array( '(', ')', ' ' ), '_', basename( $_FILES['image']['name'] ) ) );
				$target_path = $settings['upload']['folder']['maps'] . $filename; 
				while(file_exists($target_path))
				{
					$filename = md5(time().rand()).'.'.$ext;
					$target_path = $settings['upload']['folder']['maps'] . $filename; 
				}
				//var_dump($target_path);
				if(filesize($_FILES['image']['tmp_name']) > 2097152)
				{
					$this->params['error'] = 'Uploaded file cannot exceed 1MB.';
					return;
				}
				if(move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
					$db->query("INSERT INTO tf2stats_map_images (map_id, player_id, image) VALUES(%s, %s, %s)",
						array($map_info['id'],$user->id(),$filename));
			    	$this->params['success'] = basename( $_FILES['image']['name']).' uploaded successfully.';
				} else{
					echo $_FILES['image']['tmp_name'];
					echo $target_path;
			    	$this->params['error'] = 'Unknown error. Please nag FireSlash until he fixes it.';
				}
								
				
			}
			return;
		}
		
		
		
		// tinyMCE setup
		$this->head .= '<script type="text/javascript" src="/static/js/tiny_mce/jquery.tinymce.js"></script>
		<script type="text/javascript">
	$().ready(function() {
		$(\'textarea.tinymce\').tinymce({
			// Location of TinyMCE script
			script_url : "/static/js/tiny_mce/tiny_mce.js",

			theme : "advanced",
			mode : "none",
			plugins : "bbcode",
			theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,styleselect,removeformat,cleanup,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_styles : "Code=codeStyle;Quote=quoteStyle",
			content_css : "css/bbcode.css",
			entity_encoding : "raw",
			add_unload_trigger : false,
			remove_linebreaks : false,
			inline_styles : false,
			convert_fonts_to_spans : false,
			apply_source_formatting : false
			
		});
	});
</script>
		';
		
		
		
		// map info
		$this->template="manage_map";
		
		
		
		
		
		require_once('classes/map.php');
		$m = new map($this->request[0]);
		$this->params['images'] = $m->get_images('xy165');
		$this->params['has_images'] = ($this->params['images']);
		$this->params['can_set_official'] = IsUserAdmin( );
		
		// associated peoples
		$db->query("SELECT p.id, p.name, mp.type from tf2stats_map_to_player mp LEFT JOIN tf2_players p ON mp.player_id = p.id WHERE mp.map_id = %s",array($map_info['id']));
		while($row = $db->fetch_array())
		{
			$row['del_link'] = sprintf('/manage_map/%s/delauthor/%s/',$this->request[0],$row['id']);
			$p[] = $row;
		}
		$this->title = sprintf("Managing %s",htmlspecialchars($this->request[0]));
		$this->params['people'] = $p;
		
	}
}
?>