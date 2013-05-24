<?php 
require_once('classes/view.php');

class ref_report_view extends view
{
	public function prepare()
	{
		if(IsUserAdmin())
		{
			
			$this->template="ref_report";
			$this->tab="more";
			
			global $db;
			
			$db->query("SELECT * FROM tf2stats_ref ORDER BY count DESC");
			while($row = $db->fetch_array())
			{
				$row[ 'source_display' ] = strlen( $row[ 'source' ] ) > 62 ? substr( $row[ 'source' ], 0, 62 ) . "..." : $row[ 'source' ];
				$row[ 'dest_display' ] = urldecode( $row[ 'dest' ] );
				
				$rows[] = $row;
			}
			
			$this->params['refs'] = $rows;
		}
	}
}

?>